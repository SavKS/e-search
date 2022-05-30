<?php

namespace Savks\ESearch\Commands;

use Illuminate\Support\Arr;
use RuntimeException;
use Savks\ESearch\Manager\Manager;
use Savks\ESearch\Manager\ResourcesRepository;
use Symfony\Component\Console\Input\InputOption;

use Illuminate\Console\{
    Command as BaseCommand,
    ConfirmableTrait
};
use Savks\ESearch\Support\{
    MutableResource,
    Resource
};

abstract class Command extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * @return array<string, class-string<MutableResource>>
     */
    protected function choiceResources(): array
    {
        $resources = app(ResourcesRepository::class)->mutableOnly();

        if (! $resources) {
            return [];
        }

        if ($this->option('all-resources')) {
            return $resources;
        }

        if ($selectedResource = $this->option('resource')) {
            if (\is_subclass_of($selectedResource, Resource::class)) {
                if (! \is_subclass_of($selectedResource, MutableResource::class)) {
                    $this->error('The selected resource is not writable');

                    return [];
                }

                return \array_filter(
                    $resources,
                    static function (string $resource) use ($selectedResource) {
                        return \ltrim($resource, '\\') === \ltrim($selectedResource, '\\');
                    }
                );
            }

            return Arr::only($resources, [$selectedResource]);
        } else {
            $choice = $this->choice(
                'Which resources would you like to process?',
                array_merge(
                    [
                        'Process all resources',
                    ],
                    \array_keys($resources)
                )
            );
        }

        return $choice !== 'Process all resources' ?
            Arr::only($resources, [$choice]) :
            $resources;
    }

    /**
     * @return array|null
     */
    protected function resolveCriteria(): ?array
    {
        $criteriaJSON = $this->option('criteria');

        if (empty($criteriaJSON)) {
            return null;
        }

        $criteria = \is_string($criteriaJSON) ? \json_decode($criteriaJSON, true) : false;

        if ($criteria === false) {
            throw new RuntimeException('Invalid criteria. The criteria must be valid JSON');
        }

        return $criteria;
    }

    /**
     * @return Manager
     */
    protected function makeManager(): Manager
    {
        return new Manager(
            $this->option('connection')
        );
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['resource', null, InputOption::VALUE_OPTIONAL, 'Resource name.'],
            ['all-resources', null, InputOption::VALUE_NONE, 'Process all resources.'],
            ['criteria', null, InputOption::VALUE_OPTIONAL, 'Resource criteria.'],
            ['connection', null, InputOption::VALUE_OPTIONAL, 'Resource connection.'],
        ];
    }
}
