<?php

namespace Savks\ESearch\Elasticsearch;

use InvalidArgumentException;
use Savks\ESearch\Exceptions\InvalidConfiguration;

class ConnectionsManager
{
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

        $this->connections[$name] = new Connection($name, $config);

        return $this->connections[$name];
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
        $name ??= config('e-search.default_connection');

        if (! $name) {
            throw new InvalidConfiguration('Default connection name is not defined');
        }

        $config = config("e-search.connections.{$name}");

        if (! $config) {
            throw new InvalidConfiguration("Connection with name \"{$name}\" not defined");
        }

        return new Connection($name, $config);
    }
}
