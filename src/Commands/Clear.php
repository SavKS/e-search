<?php

namespace Savks\ESearch\Commands;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Savks\ESearch\Builder\DSL\Query;
use Savks\ESearch\Exceptions\EmptyQuery;
use Savks\ESearch\Support\MutableResource;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

use Savks\ESearch\Manager\{
    Manager,
    ResourcesRepository
};

class Clear extends Command
{
    /**
     * @var string
     */
    protected $name = 'e-search:clear';

    /**
     * @var string
     */
    protected $description = 'Clear data';

    /**
     * @return void
     * @throws BindingResolutionException
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
                $this->getOutput()->write("[<fg=yellow>Start clear resource data</>] {$name}", true);
            }

            /** @var MutableResource $mutableResource */
            $mutableResource = \app(ResourcesRepository::class)->make($name);

            $this->clean($mutableResource);

            if (! $this->option('hide-resource-info')) {
                $this->getOutput()->write("[<fg=green>Resource was cleared</>] {$name}", true);
            }
        }
    }

    /**
     * @param MutableResource $resource
     * @return void
     */
    protected function clean(MutableResource $resource): void
    {
        $itemsLimit = (int)($this->option('items-limit') ?: 100);

        /** @var ProgressBar|null $bar */
        $bar = null;

        $manager = $this->makeManager();

        $resource->prepareClean(
            null,
            $itemsLimit,
            function (Collection|Query $predicate) use ($manager, $itemsLimit, &$bar, $resource) {
                if ($predicate instanceof Query) {
                    $query = $predicate;

                    if ($query->isEmpty()) {
                        throw new EmptyQuery('Delete query is empty');
                    }

                    $manager->deleteByQuery($resource, $query);

                    if ($bar->getProgress() + $itemsLimit > $bar->getMaxSteps()) {
                        $bar->finish();
                    } else {
                        $bar->advance($itemsLimit);
                    }
                } else {
                    $items = $predicate;

                    if ($items->isNotEmpty()) {
                        $manager->bulkDelete(
                            $resource,
                            $items->pluck(
                                $resource->documentIdBy()
                            )
                        );

                        $bar->advance(
                            $items->count()
                        );
                    }
                }
            },
            function (int $count) use (&$bar) {
                if ($count) {
                    $bar = $this->getOutput()->createProgressBar($count);
                }
            },
            $this->resolveCriteria() ?: []
        );

        if ($bar) {
            $this->warn("\n");
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
            ]
        );
    }
}
