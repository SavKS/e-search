<?php

namespace Savks\ESearch\Commands;

use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Models\ESearchUpdate;
use Savks\ESearch\Resources\ResourcesRepository;
use Savks\ESearch\Support\MutableResource;
use Savks\ESearch\Updates\Runner;
use Symfony\Component\Console\Input\InputOption;

class UpdatesRun extends Command
{
    protected $name = 'e-search:updates:run';

    protected $description = 'Run resource updates';

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

        $manager = $this->makeClient();

        foreach ($resourceFQNs as $name => $resourceFQN) {
            if (! $this->option('hide-resource-info')) {
                $this->getOutput()->write("[<fg=yellow>Start updating resource</>] {$name}", true);
            }

            /** @var MutableResource $mutableResource */
            $mutableResource = \app(ResourcesRepository::class)->make($name);

            $count = $this->runUpdates($mutableResource, $manager);

            if (! $this->option('hide-resource-info')) {
                if ($count !== null) {
                    if ($count) {
                        $this->getOutput()->write("[<fg=green>Resource was updated</>] {$name}", true);
                    } else {
                        $this->getOutput()->write("[<fg=yellow>Resource not have new updates</>] {$name}", true);
                    }
                }
            }
        }
    }

    protected function runUpdates(MutableResource $resource, Client $manager): ?int
    {
        $runner = new Runner($resource, $manager->connection);

        if (! $runner->hasUpdates()) {
            $this->warn('  - The update list is empty');

            return null;
        }

        return $runner->apply(function (ESearchUpdate $update) {
            $this->warn("  [*] Applied update for \"{$update->type}\": \"{$update->name}\"");
        });
    }

    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),

            ['items-limit', null, InputOption::VALUE_OPTIONAL, 'Limit items per iteration.'],
            ['hide-resource-info', null, InputOption::VALUE_NONE, 'Hide resource info.'],
            ['with-query-log', null, InputOption::VALUE_NONE, 'Run with query log.'],
        ];
    }
}
