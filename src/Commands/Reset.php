<?php

namespace Savks\ESearch\Commands;

use Symfony\Component\Console\Input\InputOption;

class Reset extends Command
{
    /**
     * @var string
     */
    protected $name = 'e-search:reset';

    /**
     * @var string
     */
    protected $description = 'Recreate index and seed data';

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
            $this->call(Init::class, [
                '--force-recreate' => true,
                '--force' => true,
                '--resource' => $name,
                '--criteria' => $this->option('criteria') ?
                    \json_encode($this->option('criteria')) :
                    null,
            ]);

            $this->call(Seed::class, [
                '--items-limit' => $this->option('items-limit'),
                '--with-query-log' => $this->option('with-query-log'),

                '--resource' => $name,
                '--criteria' => $this->option('criteria') ?
                    \json_encode($this->option('criteria')) :
                    null,
            ]);
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
