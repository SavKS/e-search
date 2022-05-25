<?php

namespace Savks\ESearch\Support;

use LogicException;
use RuntimeException;

class ResourcesRepository
{
    /**
     * @var array<string, class-string<Resource>>
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
     * @return array<string, class-string<MutableResource>>
     */
    public function mutableOnly(): array
    {
        $result = [];

        foreach ($this->items as $name => $resourceFQN) {
            if (\is_subclass_of($resourceFQN, MutableResource::class)) {
                $result[$name] = $resourceFQN;
            }
        }

        return $result;
    }

    /**
     * @param class-string<Resource> $nameOrFQN
     * @return Resource
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
     * @param string $resourceFQN
     * @param string|null $name
     * @return $this
     */
    public function register(string $resourceFQN, string $name = null): ResourcesRepository
    {
        if (! is_subclass_of($resourceFQN, Resource::class)) {
            throw new LogicException("[{$resourceFQN}] must be subclass of [" . Resource::class . ']');
        }

        $this->items[$name ?? $resourceFQN::name()] = $resourceFQN;

        return $this;
    }
}
