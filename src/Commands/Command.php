<?php

namespace Savks\ESearch\Commands;

use Closure;
use Illuminate\Support\Arr;
use LogicException;
use RuntimeException;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Resources\ResourcesRepository;
use Savks\ESearch\Support\MutableResource;
use Symfony\Component\Console\Input\InputOption;

use Illuminate\Console\{
    Command as BaseCommand,
    ConfirmableTrait
};
use Savks\ESearch\Exceptions\{
    CommandFailed,
    CommandTerminated
};

abstract class Command extends BaseCommand
{
    use ConfirmableTrait;

    protected function runtimeWrapper(Closure $handler): void
    {
        try {
            $handler();
        } catch (CommandTerminated $e) {
            $this->getOutput()->write(
                sprintf(
                    '[<fg=white>%s</>] <fg=yellow>%s</>: %s',
                    now()->toDateTimeString(),
                    'Terminated',
                    $e->getMessage()
                ),
                true
            );
        } catch (CommandFailed $e) {
            $this->getOutput()->write(
                sprintf(
                    '[<fg=white>%s</>][%s] <fg=red>%s</>',
                    now()->toDateTimeString(),
                    'Failed',
                    $e->getMessage()
                ),
                true
            );
        }
    }

    protected function resolveIndexName(MutableResource $resource, Client $client): string
    {
        return $client->connection->resolveIndexName(
            $resource->indexName()
        );
    }

    /**
     * @return array<string, class-string<MutableResource>>
     */
    protected function choiceResources(bool $isSingularChoice = false): array
    {
        $resources = app(ResourcesRepository::class)->mutableOnly();

        $selectedResource = $this->option('resource');

        if ($selectedResource) {
            if (\class_exists($selectedResource)) {
                if (! \is_subclass_of($selectedResource, MutableResource::class)) {
                    throw new LogicException("The selected \"{$selectedResource}\" resource is not mutable.");
                }

                return [
                    $selectedResource::name() => $selectedResource,
                ];
            }

            return Arr::only($resources, [$selectedResource]);
        }

        if (! $resources) {
            return [];
        }

        if ($this->option('all-resources')) {
            if ($isSingularChoice) {
                throw new LogicException('The "--all-resources" option is not compatible with the "--index-name" option.');
            }

            return $resources;
        }

        if ($selectedResource) {
            return Arr::only($resources, [$selectedResource]);
        } else {
            $choice = $this->choice('Which resources would you like to process?', [
                'Process all resources',

                ...\array_keys($resources),
            ]);
        }

        return $choice !== 'Process all resources' ?
            Arr::only($resources, [$choice]) :
            $resources;
    }

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

    protected function makeClient(): Client
    {
        return new Client(
            $this->option('connection')
        );
    }

    protected function getOptions(): array
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['resource', null, InputOption::VALUE_OPTIONAL, 'Resource name.'],
            ['all-resources', null, InputOption::VALUE_NONE, 'Process all resources.'],
            ['criteria', null, InputOption::VALUE_OPTIONAL, 'Resource criteria.'],
            ['connection', null, InputOption::VALUE_OPTIONAL, 'Resource connection.'],
            ['index-name', null, InputOption::VALUE_OPTIONAL, 'Custom index name.'],
        ];
    }
}
