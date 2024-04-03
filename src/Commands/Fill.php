<?php

namespace Savks\ESearch\Commands;

use DB;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Support\MutableResource;
use Str;
use Symfony\Component\Console\Input\InputOption;

use Illuminate\Support\{
    Arr,
    Collection
};
use Savks\ESearch\Exceptions\{
    CommandFailed,
    CommandTerminated
};

class Fill extends Command
{
    protected $name = 'e-search:fill';

    protected $description = 'Fill indices';

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
            $this->runtimeWrapper(function () use ($resourceFQN, $client, $name) {
                /** @var MutableResource $resource */
                $resource = new $resourceFQN();

                $datetimeSuffix = now()->format('Y_m_d_His') . '_' . strtolower(Str::random(6));

                $indexOriginName = $this->option('index-name') ?? $resource->indexName();

                $resource->useIndex("{$indexOriginName}_{$datetimeSuffix}");

                $this->getOutput()->write(
                    sprintf(
                        '[<fg=white>%s</>] Start filling resource <fg=green>%s</> to index alias <fg=blue>%s</>.',
                        now()->toDateTimeString(),
                        $name,
                        $client->connection->resolveIndexName($indexOriginName)
                    ),
                    true
                );

                $this->prepareIndex($resource, $indexOriginName, $datetimeSuffix, $client);

                if (! $this->option('no-seed')) {
                    $this->seed($resource, $client);
                }

                $this->waitForIndexToBeReady($resource, $client);

                $this->assignIndexAlias($resource, $indexOriginName, $client);

                $this->getOutput()->write(
                    sprintf(
                        '[<fg=white>%s</>] Done.',
                        now()->toDateTimeString(),
                    ),
                    true
                );
            });
        }
    }

    protected function prepareIndex(
        MutableResource $resource,
        string $indexOriginName,
        string $datetimeSuffix,
        Client $client
    ): void {
        $this->prepareForAliasCreating($indexOriginName, $client);

        $aliasFullName = $client->connection->resolveIndexName(
            $indexOriginName
        );

        $indexFullName = $aliasFullName . '_' . $datetimeSuffix;

        $client->connection->client()->indices()->create([
            'index' => $indexFullName,
            'body' => $resource->index(),
        ]);

        $client->connection->client()->indices()->putMapping([
            'index' => $indexFullName,
            'body' => $resource->prepareMapping(),
        ]);

        $this->getOutput()->write(
            sprintf(
                '[<fg=white>%s</>] Index <fg=cyan>%s</> created.',
                now()->toDateTimeString(),
                $indexFullName
            ),
            true
        );
    }

    protected function waitForIndexToBeReady(MutableResource $resource, Client $client): void
    {
        $client->elasticsearchClient()->indices()->refresh([
            'index' => $client->connection->resolveIndexName($resource->indexName()),
        ]);
    }

    protected function prepareForAliasCreating(string $indexOriginName, Client $client): void
    {
        $aliasFullName = $client->connection->resolveIndexName(
            $indexOriginName
        );
        $aliasesInfo = $client->elasticsearchClient()->indices()->getAlias()->asArray();

        if (isset($aliasesInfo[$aliasFullName])) {
            $this->getOutput()->write(
                sprintf(
                    '[<fg=white>%s</>] The elasticsearch has an index with name <fg=cyan>%s</> (not an alias).',
                    now()->toDateTimeString(),
                    $aliasFullName
                )
            );

            if (! $this->confirm('Delete it?') && ! $this->option('force')) {
                throw new CommandTerminated(
                    'It is not possible to create an alias if you have an index with the same name.'
                );
            }

            $client->connection->client()->indices()->delete(['index' => $aliasFullName]);

            $this->getOutput()->write(
                sprintf(
                    '[<fg=white>%s</>] Index <fg=cyan>%s</> deleted.',
                    now()->toDateTimeString(),
                    $aliasFullName
                ),
                true
            );
        }
    }

    protected function seed(MutableResource $resource, Client $client): void
    {
        $indexFullName = $client->connection->resolveIndexName(
            $resource->indexName()
        );

        $this->getOutput()->write(
            sprintf(
                '[<fg=white>%s</>] Start seeding data to index <fg=cyan>%s</>.',
                now()->toDateTimeString(),
                $indexFullName
            ),
            true
        );

        $withQueryLog = $this->option('with-query-log');

        $logsPath = null;

        if ($withQueryLog) {
            DB::enableQueryLog();

            $time = time();
            $logsPath = \storage_path("app/e-search-query-logs/{$time}");

            if (! mkdir($logsPath, 0755, true) && ! \is_dir($logsPath)) {
                throw new CommandFailed("Directory \"{$logsPath}\" was not created");
            }
        }

        $itemsLimit = (int)($this->option('items-limit') ?: $resource->seedLimit());

        $bar = null;

        $totalQueriesCount = 0;
        $totalIterations = 0;

        $resource->prepareSeed(
            null,
            $itemsLimit,
            function (Collection $items) use (
                $client,
                &$bar,
                $resource,
                $withQueryLog,
                $logsPath,
                &$totalQueriesCount,
                &$totalIterations
            ) {
                $documents = [];

                foreach ($items as $item) {
                    $documents[] = $resource->prepareDocuments($item);

                    $bar?->advance();
                }

                $client->bulkSave(
                    $resource,
                    \array_merge(...$documents)
                );

                if ($withQueryLog) {
                    $queryLog = DB::getQueryLog();

                    \file_put_contents(
                        "{$logsPath}/{$totalIterations}.json",
                        \json_encode($queryLog, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE)
                    );

                    $totalQueriesCount += \count($queryLog);
                    $totalIterations++;

                    DB::flushQueryLog();
                }
            },
            function (int $count) use (&$bar) {
                $bar = $this->getOutput()->createProgressBar($count);
            },
            $this->resolveCriteria() ?: []
        );

        if ($bar) {
            $this->newLine();
        }

        if ($withQueryLog) {
            $this->getOutput()->write(
                sprintf(
                    '[<fg=white>%s</>] Query log. <fg=blue>%s</> queries in <fg=blue>%s</> iterations.',
                    now()->toDateTimeString(),
                    $totalQueriesCount,
                    $totalIterations
                ),
                true
            );

            $this->getOutput()->write(
                sprintf(
                    '[<fg=white>%s</>] Query log. <fg=blue>%s</> queries in <fg=blue>%s</> iterations. Files:',
                    now()->toDateTimeString(),
                    $totalQueriesCount,
                    $totalIterations
                ),
                true
            );

            for ($i = 0; $i < $totalIterations; $i++) {
                $this->getOutput()->write(
                    sprintf(
                        '- <fg=magenta>%s/%s.json</>',
                        $logsPath,
                        $i
                    ),
                    true
                );
            }
        }
    }

    protected function assignIndexAlias(MutableResource $resource, string $indexOriginName, Client $client): void
    {
        $aliasFullName = $client->connection->resolveIndexName(
            $indexOriginName
        );
        $indexFullName = $client->connection->resolveIndexName(
            $resource->indexName()
        );

        $aliasesInfo = $client->elasticsearchClient()->indices()->getAlias()->asArray();

        $removeOldAliasesActions = (function () use ($aliasFullName, $aliasesInfo) {
            $result = [];

            foreach ($aliasesInfo as $indexName => $data) {
                if (\array_key_exists($aliasFullName, $data['aliases'])) {
                    $result[] = [
                        'remove' => [
                            'index' => $indexName,
                            'alias' => $aliasFullName,
                        ],
                    ];
                }
            }

            return $result;
        })();

        $client->elasticsearchClient()->indices()->updateAliases([
            'body' => [
                'actions' => [
                    [
                        'add' => [
                            'index' => $indexFullName,
                            'alias' => $aliasFullName,
                        ],
                    ],

                    ...$removeOldAliasesActions,
                ],
            ],
        ]);

        $redundantIndices = Arr::pluck($removeOldAliasesActions, 'remove.index');

        if ($redundantIndices) {
            $client->connection->client()->indices()->delete([
                'index' => \implode(',', $redundantIndices),
            ]);
        }

        $this->getOutput()->write(
            sprintf(
                '[<fg=white>%s</>] The alias <fg=blue>%s</> is assigned to the index <fg=cyan>%s</>.',
                now()->toDateTimeString(),
                $aliasFullName,
                $indexFullName,
            ),
            true
        );
    }

    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),

            ['items-limit', null, InputOption::VALUE_OPTIONAL, 'Limit items per iteration.'],
            ['with-query-log', null, InputOption::VALUE_NONE, 'Run with query log.'],
            ['no-seed', null, InputOption::VALUE_NONE, 'Without data seeding.'],
        ];
    }
}
