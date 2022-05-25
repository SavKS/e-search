<?php

namespace Savks\ESearch\Commands;

use ESearch;
use Savks\ESearch\Models\ESearchUpdate;
use Savks\ESearch\Support\MutableResource;
use Savks\ESearch\Updates\Runner;
use Symfony\Component\Console\Input\InputOption;

use Elastic\Elasticsearch\Exception\{
    ClientResponseException,
    MissingParameterException,
    ServerResponseException
};

class UpdatesRun extends Command
{
    /**
     * @var string
     */
    protected $name = 'e-search:updates:run';

    /**
     * @var string
     */
    protected $description = 'Run resource updates';

    /**
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
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
                $this->getOutput()->write("[<fg=yellow>Start updating resource</>] {$name}", true);
            }

            $count = $this->runUpdates(
                ESearch::resources()->make($name)
            );

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

    /**
     * @param MutableResource $resource
     * @return int|null
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function runUpdates(MutableResource $resource): ?int
    {
        $runner = new Runner($resource);

        if (! $runner->hasUpdates()) {
            $this->warn('  - The update list is empty');

            return null;
        }

        return $runner->apply(function (ESearchUpdate $update) {
            $this->warn("  [*] Applied update for \"{$update->type}\": \"{$update->name}\"");
        });
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
