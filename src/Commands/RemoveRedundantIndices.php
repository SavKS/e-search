<?php

namespace Savks\ESearch\Commands;

use Arr;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Resources\ResourcesRepository;
use Savks\ESearch\Support\MutableResource;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    MissingParameterException,
    ServerResponseException
};

class RemoveRedundantIndices extends Command
{
    /**
     * @var string
     */
    protected $name = 'e-search:remove-redundant-indices';

    /**
     * @var string
     */
    protected $description = 'Fill indices';

    /**
     * @return void
     */
    public function handle(): void
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $resourceFQNs = $this->choiceResources(
            (bool)$this->option('index-name')
        );

        if (! $resourceFQNs) {
            $this->warn('No mutable resources found...');

            return;
        }

        $client = $this->makeClient();

        foreach ($resourceFQNs as $name => $resourceFQN) {
            $this->runtimeWrapper(function () use ($client, $name) {
                /** @var MutableResource $resource */
                $resource = \app(ResourcesRepository::class)->make($name);

                if ($this->option('index-name')) {
                    $resource->useIndex(
                        $this->option('index-name')
                    );
                }

                $this->process($resource, $client);
            });
        }
    }

    /**
     * @param MutableResource $resource
     * @param Client $client
     * @return void
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
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
            $pattern = '/^' . \preg_quote($aliasName) . '_(\d{4}_\d{2}_\d{2}_\d{6})_\w{6}$/';

            foreach ($aliasesInfo as $indexName => $data) {
                $isMatched = \preg_match($pattern, $indexName, $matches) > 0;

                if (! $isMatched) {
                    continue;
                }

                if (empty($data['aliases'])) {
                    $result[] = $indexName;
                } else {
                    if ($currentIndexName) {
                        \preg_match($pattern, $currentIndexName, $currentMatches);

                        $currentDatetime = (int)\str_replace(
                            '_',
                            '',
                            \preg_replace('/_\w{6}$/', '', $currentMatches[1])
                        );

                        $datetime = (int)\str_replace(
                            '_',
                            '',
                            \preg_replace('/_\w{6}$/', '', $matches[1])
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
            'index' => \implode(',', $redundantIndices),
        ]);
    }
}
