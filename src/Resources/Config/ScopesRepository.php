<?php

namespace Savks\ESearch\Resources\Config;

use Closure;

class ScopesRepository
{
    /**
     * @var Closure[]
     */
    protected array $callbacks = [];

    /**
     * @return Closure[]
     */
    public function all(): array
    {
        return $this->callbacks;
    }

    public function add(string $name, Closure $callback): self
    {
        $this->callbacks[$name] = $callback;

        return $this;
    }

    public function findByName(string $name): ?Closure
    {
        foreach ($this->all() as $scopeName => $callback) {
            if ($scopeName === $name) {
                return $callback;
            }
        }

        return null;
    }
}
