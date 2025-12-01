<?php

namespace Savks\ESearch\Commands;

use Arr;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Resources\ResourcesRepository;
use Savks\ESearch\Support\MutableResource;

class RemoveRedundantIndices extends Command
{
    protected $name = 'e-search:remove-redundant-indices';

    protected $description = 'Fill indices';

    public function handle(): void
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $resourceClasses = $this->choiceResources(
            (bool)$this->option('index-name')
        );

        if (! $resourceClasses) {
            $this->warn('No mutable resources found...');

            return;
        }

        foreach ($resourceClasses as $name => $resourceClass) {
            $this->runtimeWrapper(function () use ($name) {
                /** @var MutableResource<mixed> $resource */
                $resource = app(ResourcesRepository::class)->make($name);

                $client = $resource::newClient();

                if ($this->option('index-name')) {
                    $resource->useIndex(
                        $this->option('index-name')
                    );
                }

                $this->process($resource, $client);
            });
        }
    }

    protected function process(MutableResource $resource, Client $client): void
    {
        $aliasName = $client->connection->resolveIndexName(
            $resource->indexName()
        );

        $aliasesInfo = $client->connection->client()->indices()->getAlias([
            'index' => "{$aliasName}_*",
        ])->asArray();

        $deleteCandidates = (function () use ($aliasName, $aliasesInfo) {
            $result = [];

            $currentIndexName = null;
            $pattern = '/^' . preg_quote($aliasName) . '_(\d{4}_\d{2}_\d{2}_\d{6})_\w{6}$/';

            foreach ($aliasesInfo as $indexName => $data) {
                $isMatched = preg_match($pattern, $indexName, $matches) > 0;

                if (! $isMatched) {
                    continue;
                }

                if (empty($data['aliases'])) {
                    $result[] = $indexName;
                } else {
                    if ($currentIndexName) {
                        preg_match($pattern, $currentIndexName, $currentMatches);

                        $currentDatetime = (int)str_replace(
                            '_',
                            '',
                            preg_replace('/_\w{6}$/', '', $currentMatches[1])
                        );

                        $datetime = (int)str_replace(
                            '_',
                            '',
                            preg_replace('/_\w{6}$/', '', $matches[1])
                        );

                        if ($datetime > $currentDatetime) {
                            $currentIndexName = $indexName;
                        }
                    } else {
                        $currentIndexName = $indexName;
                    }
                }
            }

            return $result;
        })();

        if (! $deleteCandidates) {
            return;
        }

        $this->getOutput()->write(
            sprintf(
                '[<fg=white>%s</>] Found redundant indices for resource <fg=green>%s</>.',
                now()->toDateTimeString(),
                $resource::name()
            ),
            true
        );

        $redundantIndices = $this->choice(
            'The following extra indexes were found. Select the ones you want to delete (0 is All)',
            [
                'All',

                ...$deleteCandidates,
            ]
        );

        if ($redundantIndices === 'All') {
            $redundantIndices = $deleteCandidates;
        } else {
            $redundantIndices = Arr::wrap($redundantIndices);
        }

        $client->connection->client()->indices()->delete([
            'index' => implode(',', $redundantIndices),
        ]);
    }
}
