<?php

namespace Savks\ESearch\Commands;

use Savks\ESearch\Support\MutableResource;

class Truncate extends Command
{
    protected $name = 'e-search:truncate';

    protected $description = 'Truncate indices';

    public function handle(): void
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $resourceFQNs = $this->choiceResources();

        if (! $resourceFQNs) {
            $this->warn('No mutable resources found...');

            return;
        }

        $client = $this->makeClient();

        foreach ($resourceFQNs as $name => $resourceFQN) {
            $this->runtimeWrapper(function () use ($resourceFQN, $name, $client) {
                /** @var MutableResource $resource */
                $resource = new $resourceFQN();

                if ($this->option('index-name')) {
                    $resource->useIndex(
                        $this->option('index-name')
                    );
                }

                $indexFullName = $client->connection->resolveIndexName(
                    $resource->indexName()
                );

                $this->getOutput()->write(
                    sprintf(
                        '[<fg=white>%s</>] Start truncate resource <fg=green>%s</> index alias <fg=blue>%s</>.',
                        now()->toDateTimeString(),
                        $name,
                        $indexFullName
                    ),
                    true
                );

                if ($this->option('index-name')) {
                    $resource->useIndex(
                        $this->option('index-name')
                    );
                }

                $client->truncate($resource);

                $this->getOutput()->write(
                    sprintf(
                        '[<fg=white>%s</>] Resource truncated.',
                        now()->toDateTimeString()
                    ),
                    true
                );
            });
        }
    }
}
