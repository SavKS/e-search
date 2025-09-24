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

        $resourceClasses = $this->choiceResources();

        if (! $resourceClasses) {
            $this->warn('No mutable resources found...');

            return;
        }

        $client = $this->makeClient();

        foreach ($resourceClasses as $name => $resourceClass) {
            $this->runtimeWrapper(function () use ($resourceClass, $name, $client) {
                /** @var MutableResource<mixed> $resource */
                $resource = new $resourceClass();

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
