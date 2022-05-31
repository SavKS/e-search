<?php

namespace Savks\ESearch\Commands;

use Illuminate\Contracts\Container\BindingResolutionException;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Models\ESearchUpdate;
use Savks\ESearch\Support\MutableResource;
use Savks\ESearch\Updates\Runner;
use Symfony\Component\Console\Input\InputOption;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    MissingParameterException,
    ServerResponseException
};
use Savks\ESearch\Resources\{
    ResourcesRepository
};

class Init extends Command
{
    /**
     * @var string
     */
    protected $name = 'e-search:init';

    /**
     * @var string
     */
    protected $description = 'Init indices';

    /**
     * @return void
     * @throws AuthenticationException
     * @throws BindingResolutionException
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
            $this->getOutput()->write("[<fg=yellow>Start resource init</>] {$name}", true);

            /** @var MutableResource $mutableResource */
            $mutableResource = \app(ResourcesRepository::class)->make($name);

            $this->createOrRecreateIndex($mutableResource);

            $this->getOutput()->write("[<fg=green>Resource was inited</>] {$name}", true);
        }
    }

    /**
     * @param MutableResource $resource
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     * @throws AuthenticationException
     */
    protected function createOrRecreateIndex(MutableResource $resource): void
    {
        $manager = $this->makeManager();

        $updatesRunner = new Runner($resource, $manager->connection);

        $indexName = $manager->connection->resolveIndexName(
            $resource->indexName()
        );

        $isExists = $manager
                ->connection
                ->client()
                ->indices()
                ->exists(['index' => $indexName])
                ->getStatusCode() !== 404;

        if ($isExists) {
            if ($this->option('force-recreate')
                || $this->confirm('Index already exists. Do you want recreate him?')
            ) {
                $manager->connection->client()->indices()->delete(['index' => $indexName]);

                if ($updatesRunner->hasAppliedUpdates()) {
                    $this->getOutput()->write("[<fg=cyan>Clean updates</>] {$indexName}", true);

                    $updatesRunner->clean();
                }

                $manager->connection->client()->indices()->create([
                    'index' => $indexName,
                    'body' => $resource->index(),
                ]);

                $manager->connection->client()->indices()->putMapping([
                    'index' => $indexName,
                    'body' => $resource->prepareMapping(),
                ]);

                $this->getOutput()->write("[<fg=cyan>Index recreated</>] {$indexName}", true);
            } else {
                $this->getOutput()->write("[<fg=white>Skip existing index</>] {$indexName}", true);
            }
        } else {
            $manager->connection->client()->indices()->create([
                'index' => $indexName,
                'body' => $resource->index(),
            ]);

            $manager->connection->client()->indices()->putMapping([
                'index' => $indexName,
                'body' => $resource->prepareMapping(),
            ]);

            $this->getOutput()->write("[<fg=green>Index created</>] {$indexName}", true);
        }

        $updatesRunner->apply(function (ESearchUpdate $update) {
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
                ['force-recreate', null, InputOption::VALUE_NONE, 'Force recreate indices if exists.'],
            ]
        );
    }
}
