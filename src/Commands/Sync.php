<?php

namespace Savks\ESearch\Commands;

use Symfony\Component\Console\Input\InputOption;

class Sync extends Command
{
    /**
     * @var string
     */
    protected $name = 'e-search:sync';

    /**
     * @var string
     */
    protected $description = 'Sync data';

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
            $this->getOutput()->write("[<fg=yellow>Start sync resource data</>] {$name}", true);

            if ($this->option('truncate')) {
                $this->call(Truncate::class, [
                    '--resource' => $name,
                    '--force' => true,
                    '--connection' => $this->option('connection'),
                ]);
            }

            $this->call(Seed::class, [
                '--items-limit' => $this->input->getOption('items-limit'),
                '--resource' => $name,
                '--force' => true,
                '--hide-resource-info' => true,
                '--with-query-log' => $this->input->getOption('with-query-log'),
                '--criteria' => $this->input->getOption('criteria'),
                '--connection' => $this->option('connection'),
            ]);

            $this->newLine();

            if (! $this->option('truncate')) {
                $this->call(Clear::class, [
                    '--items-limit' => $this->input->getOption('items-limit'),
                    '--resource' => $name,
                    '--force' => true,
                    '--hide-resource-info' => true,
                    '--criteria' => $this->input->getOption('criteria'),
                    '--connection' => $this->option('connection'),
                ]);
            }

            $this->getOutput()->write("[<fg=green>Resource was synced</>] {$name}", true);
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
                ['with-query-log', null, InputOption::VALUE_NONE, 'Run with query log.'],
                ['truncate', null, InputOption::VALUE_NONE, 'Truncate before seed'],
            ]
        );
    }
}
