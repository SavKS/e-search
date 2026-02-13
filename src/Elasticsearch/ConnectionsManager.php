<?php

namespace Savks\ESearch\Elasticsearch;

use Closure;
use InvalidArgumentException;
use Savks\ESearch\Elasticsearch\Connection as BaseConnection;
use Savks\ESearch\Elasticsearch\Contracts\Connection;
use Savks\ESearch\Exceptions\InvalidConfiguration;

class ConnectionsManager
{
    /**
     * @var array<string, Closure(string $name, array<string, mixed> $config):Connection>
     */
    protected array $extensions = [];

    /**
     * @var array<string, Connection>
     */
    protected array $connections;

    public function __construct(
        protected readonly array $connectionDeclarations,
        protected readonly string $defaultConnectionName
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function connectUsing(string $name, array $config, bool $force = false): Connection
    {
        if (
            ! $force
            && isset($this->connections[$name])
        ) {
            throw new InvalidArgumentException("Connection with name {$name} already exists.");
        }

        if (isset($this->extensions[$name])) {
            $this->connections[$name] = ($this->extensions[$name])($name, $config);
        } else {
            $this->connections[$name] = new BaseConnection($name, $config);
        }

        return $this->connections[$name];
    }

    /**
     * @param Closure(string $name, array<string, mixed> $config):Connection $callback
     *
     * @return $this
     */
    public function extend(string $name, Closure $callback): static
    {
        $this->extensions[$name] = $callback;

        return $this;
    }

    public function forgetExtension(string $name): static
    {
        unset($this->extensions[$name]);

        return $this;
    }

    public function purge(string $name): static
    {
        unset($this->connections[$name]);

        return $this;
    }

    public function resolveDefault(): Connection
    {
        return $this->resolve($this->defaultConnectionName);
    }

    public function resolve(string $name): Connection
    {
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    protected function createConnection(?string $name = null): Connection
    {
        $name ??= config()->string('e-search.default_connection');

        if (! $name) {
            throw new InvalidConfiguration('Default connection name is not defined');
        }

        /** @var array<string, mixed> $config */
        $config = config("e-search.connections.{$name}");

        if (! $config) {
            throw new InvalidConfiguration("Connection with name \"{$name}\" not defined");
        }

        if (isset($this->extensions[$name])) {
            return ($this->extensions[$name])($name, $config);
        }

        return new BaseConnection($name, $config);
    }
}
