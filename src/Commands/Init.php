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

            $this->createOrRecreateIndex(
                ESearch::resources()->make($name)
            );

            $this->getOutput()->write("[<fg=green>Resource was inited</>] {$name}", true);
        }
    }

    /**
     * @param MutableResource $resource
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function createOrRecreateIndex(MutableResource $resource): void
    {
        $updatesRunner = new Runner($resource);

        $name = $resource->prefixedIndexName();

        $isExists = ESearch::client()->indices()->exists(['index' => $name])->getStatusCode() !== 404;

        if ($isExists) {
            if ($this->option('force-recreate')
                || $this->confirm('Index already exists. Do you want recreate him?')
            ) {
                ESearch::client()->indices()->delete(['index' => $name]);

                if ($updatesRunner->hasAppliedUpdates()) {
                    $this->getOutput()->write("[<fg=cyan>Clean updates</>] {$name}", true);

                    $updatesRunner->clean();
                }

                ESearch::client()->indices()->create([
                    'index' => $name,
                    'body' => $resource->index(),
                ]);

                ESearch::client()->indices()->putMapping([
                    'index' => $name,
                    'body' => $resource->prepareMapping(),
                ]);

                $this->getOutput()->write("[<fg=cyan>Index recreated</>] {$name}", true);
            } else {
                $this->getOutput()->write("[<fg=white>Skip existing index</>] {$name}", true);
            }
        } else {
            ESearch::client()->indices()->create([
                'index' => $name,
                'body' => $resource->index(),
            ]);

            ESearch::client()->indices()->putMapping([
                'index' => $name,
                'body' => $resource->prepareMapping(),
            ]);

            $this->getOutput()->write("[<fg=green>Index created</>] {$name}", true);
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
