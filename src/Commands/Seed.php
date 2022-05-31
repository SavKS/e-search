<?php

namespace Savks\ESearch\Commands;

use DB;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Savks\ESearch\Resources\ResourcesRepository;
use Savks\ESearch\Support\MutableResource;
use Symfony\Component\Console\Input\InputOption;

use Elastic\Elasticsearch\Exception\{
    ClientResponseException,
    MissingParameterException,
    ServerResponseException
};

class Seed extends Command
{
    /**
     * @var string
     */
    protected $name = 'e-search:seed';

    /**
     * @var string
     */
    protected $description = 'Seed data';

    /**
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $resourceFQNs = $this->choiceResources();

        if (! $resourceFQNs) {
            $this->warn('No writable resources found...');

            return;
        }

        foreach ($resourceFQNs as $name => $resourceFQN) {
            if (! $this->option('hide-resource-info')) {
                $this->getOutput()->write("[<fg=yellow>Start seeding resource</>] {$name}", true);
            }

            /** @var MutableResource $mutableResource */
            $mutableResource = \app(ResourcesRepository::class)->make($name);

            $this->seed($mutableResource);

            if (! $this->option('hide-resource-info')) {
                $this->getOutput()->write("\n[<fg=green>Resource was seeded</>] {$name}", true);
            }
        }
    }

    /**
     * @param MutableResource $resource
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function seed(MutableResource $resource): void
    {
        $withQueryLog = $this->option('with-query-log');

        $logsPath = null;

        if ($withQueryLog) {
            DB::enableQueryLog();

            $time = time();
            $logsPath = \storage_path("app/e-search-query-logs/{$time}");

            if (! mkdir($logsPath, 0755, true) && ! \is_dir($logsPath)) {
                $this->error(
                    sprintf('Directory "%s" was not created', $logsPath)
                );

                return;
            }
        }

        $itemsLimit = (int)($this->option('items-limit') ?: 100);

        $bar = null;

        $totalQueriesCount = 0;
        $totalIterations = 0;

        $manager = $this->makeManager();

        $resource->prepareSeed(
            null,
            $itemsLimit,
            function (Collection $items) use (
                $manager,
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

                $manager->bulkSave(
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

        if (! $bar) {
            $this->info('Done.');
        }

        if ($withQueryLog) {
            $this->getOutput()->write(
                "\n\n[<fg=yellow>Query log</>] <fg=blue>{$totalQueriesCount}</> queries in <fg=blue>{$totalIterations}</> iterations.",
                true
            );
            $this->getOutput()->write(
                "[<fg=yellow>Result files</>] - {$logsPath}/0.json",
                true
            );

            for ($i = 1; $i < $totalIterations; $i++) {
                $this->getOutput()->write(
                    "               - {$logsPath}/{$i}.json",
                    true
                );
            }
        }
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return \array_merge(
            parent::getOptions(),
            [
                ['items-limit', null, InputOption::VALUE_OPTIONAL, 'Limit items per iteration.'],
                ['hide-resource-info', null, InputOption::VALUE_NONE, 'Hide resource info.'],
                ['with-query-log', null, InputOption::VALUE_NONE, 'Run with query log.'],
            ]
        );
    }
}
