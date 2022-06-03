<?php

namespace Savks\ESearch\Commands;

use Arr;
use DB;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Models\ESearchUpdate;
use Savks\ESearch\Resources\ResourcesRepository;
use Savks\ESearch\Support\MutableResource;
use Savks\ESearch\Updates\Runner;
use Symfony\Component\Console\Input\InputOption;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    MissingParameterException,
    ServerResponseException
};
use Savks\ESearch\Exceptions\{
    CommandFailed,
    CommandTerminated
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

                $this->getOutput()->write(
                    sprintf(
                        '[<fg=white>%s</>] Start removing redundant indices for resource <fg=green>%s</>.',
                        now()->toDateTimeString(),
                        $name
                    ),
                    true
                );

                $status = $this->remove($resource, $client);

                if ($status) {
                    $this->getOutput()->write(
                        sprintf(
                            '[<fg=white>%s</>] Done.',
                            now()->toDateTimeString(),
                        ),
                        true
                    );
                } else {
                    $this->getOutput()->write(
                        sprintf(
                            '[<fg=white>%s</>] <fg=yellow>Nothing to delete</>.',
                            now()->toDateTimeString(),
                        ),
                        true
                    );
                }
            });
        }
    }

    /**
     * @param MutableResource $resource
     * @param Client $client
     * @return bool
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function remove(MutableResource $resource, Client $client): bool
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
            $pattern = '/^' . \preg_quote($aliasName) . '_(\d{4}_\d{2}_\d{2}_\d{6})$/';

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

                        $currentDatetime = (int)\str_replace('_', '', $currentMatches[1]);
                        $datetime = (int)\str_replace('_', '', $matches[1]);

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
            return false;
        }

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

        return true;
    }
}
