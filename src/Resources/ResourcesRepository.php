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
        foreach ($config as $name => $resourceFQN) {
            if (! is_subclass_of($resourceFQN, Resource::class)) {
                throw new LogicException("[{$resourceFQN}] must be subclass of [" . Resource::class . ']');
            }

            $this->items[is_int($name) ? $resourceFQN::name() : $name] = $resourceFQN;
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

        foreach ($this->items as $name => $resourceFQN) {
            if (is_subclass_of($resourceFQN, MutableResource::class)) {
                $result[$name] = $resourceFQN;
            }
        }

        return $result;
    }

    /**
     * @param class-string<Resource> $nameOrFQN
     */
    public function make(string $nameOrFQN): Resource
    {
        $resourceFQNs = $this->all();

        if (isset($resourceFQNs[$nameOrFQN])) {
            $resourceFQN = $resourceFQNs[$nameOrFQN];
        } elseif ($index = array_search($nameOrFQN, $resourceFQNs, true)) {
            $resourceFQN = $resourceFQNs[$index];
        } else {
            throw new RuntimeException("Resource [{$nameOrFQN}] not found");
        }

        return new $resourceFQN();
    }

    /**
     * @param class-string<Resource> $resourceFQN
     */
    public function register(string $resourceFQN, ?string $name = null): ResourcesRepository
    {
        $this->items[$name ?? $resourceFQN::name()] = $resourceFQN;

        return $this;
    }
}
