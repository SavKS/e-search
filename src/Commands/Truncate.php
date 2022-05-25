<?php

namespace Savks\ESearch\Commands;

use ESearch;
use Symfony\Component\Console\Input\InputOption;

class Truncate extends Command
{
    /**
     * @var string
     */
    protected $name = 'e-search:truncate';

    /**
     * @var string
     */
    protected $description = 'Truncate data';

    /**
     * @return void
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
                $this->getOutput()->write("[<fg=yellow>Start truncate resource data</>] {$name}", true);
            }

            ESearch::truncate(
                ESearch::resources()->make($name)
            );

            if (! $this->option('hide-resource-info')) {
                $this->getOutput()->write("[<fg=green>Resource was truncated</>] {$name}", true);
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
                ['hide-resource-info', null, InputOption::VALUE_NONE, 'Hide resource info.'],
            ]
        );
    }
}
