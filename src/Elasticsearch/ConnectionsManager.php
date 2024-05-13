<?php

namespace Savks\ESearch\Elasticsearch;

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
        $name ??= \config('e-search.default_connection');

        if (! $name) {
            throw new InvalidConfiguration('Default connection name is not defined');
        }

        $config = \config("e-search.connections.{$name}");

        if (! $config) {
            throw new InvalidConfiguration("Connection with name \"{$name}\" not defined");
        }

        return new Connection($name, $config);
    }
}
