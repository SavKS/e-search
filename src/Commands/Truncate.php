<?php

namespace Savks\ESearch\Commands;

use Illuminate\Contracts\Container\BindingResolutionException;
use Savks\ESearch\Manager\ResourcesRepository;
use Savks\ESearch\Support\MutableResource;
use Symfony\Component\Console\Input\InputOption;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    MissingParameterException,
    ServerResponseException
};

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
     * @throws BindingResolutionException
     * @throws AuthenticationException
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

        $manager = $this->makeManager();

        foreach ($resourceFQNs as $name => $resourceFQN) {
            if (! $this->option('hide-resource-info')) {
                $this->getOutput()->write("[<fg=yellow>Start truncate resource data</>] {$name}", true);
            }

            /** @var MutableResource $mutableResource */
            $mutableResource = \app(ResourcesRepository::class)->make($name);

            $manager->truncate($mutableResource);

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
