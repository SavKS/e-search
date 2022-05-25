<?php

namespace Savks\ESearch\Support\Config;

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

    /**
     * @param string $name
     * @param Closure $callback
     * @return $this
     */
    public function add(string $name, Closure $callback): self
    {
        $this->callbacks[$name] = $callback;

        return $this;
    }

    /**
     * @param string $name
     * @return Closure|null
     */
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
