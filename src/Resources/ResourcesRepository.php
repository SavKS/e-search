<?php

namespace Savks\ESearch\Resources;

use LogicException;
use RuntimeException;
use Savks\ESearch\Support\MutableResource;
use Savks\ESearch\Support\Resource;

class ResourcesRepository
{
    /**
     * @var array<string, class-string<Resource>|class-string<MutableResource<mixed>>>
     */
    protected array $items = [];

    /**
     * @param array<int, string>|array<string, string> $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $name => $resource) {
            if (! is_subclass_of($resource, Resource::class)) {
                throw new LogicException("[{$resource}] must be subclass of [" . Resource::class . ']');
            }

            $this->items[is_int($name) ? $resource::name() : $name] = $resource;
        }
    }

    /**
     * @return array<string, class-string<Resource>>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, class-string<MutableResource<mixed>>>
     */
    public function mutableOnly(): array
    {
        $result = [];

        foreach ($this->items as $name => $resource) {
            if (is_subclass_of($resource, MutableResource::class)) {
                $result[$name] = $resource;
            }
        }

        return $result;
    }

    /**
     * @param class-string<Resource> $nameOrClass
     */
    public function make(string $nameOrClass): Resource
    {
        $resourceClasses = $this->all();

        if (isset($resourceClasses[$nameOrClass])) {
            $resourceClass = $resourceClasses[$nameOrClass];
        } elseif ($index = array_search($nameOrClass, $resourceClasses, true)) {
            $resourceClass = $resourceClasses[$index];
        } else {
            throw new RuntimeException("Resource [{$nameOrClass}] not found");
        }

        return new $resourceClass();
    }

    /**
     * @param class-string<Resource> $resource
     */
    public function register(string $resource, ?string $name = null): ResourcesRepository
    {
        $this->items[$name ?? $resource::name()] = $resource;

        return $this;
    }
}
