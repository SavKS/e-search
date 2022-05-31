<?php

namespace Savks\ESearch\Elasticsearch;

use Savks\ESearch\Exceptions\InvalidConfiguration;

class ConnectionsManager
{
    /**
     * @var array<string, Connection>
     */
    protected array $connections;

    /**
     * @var array
     */
    protected array $connectionDeclarations;

    /**
     * @var string
     */
    protected string $defaultConnectionName;

    /**
     * @param array $connectionDeclarations
     * @param string $defaultConnectionName
     */
    public function __construct(array $connectionDeclarations, string $defaultConnectionName)
    {
        $this->connectionDeclarations = $connectionDeclarations;
        $this->defaultConnectionName = $defaultConnectionName;
    }

    /**
     * @return Connection
     */
    public function resolveDefault(): Connection
    {
        return $this->resolve($this->defaultConnectionName);
    }

    /**
     * @param string $name
     * @return Connection
     */
    public function resolve(string $name): Connection
    {
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * @param string|null $name
     * @return Connection
     */
    protected function createConnection(string $name = null): Connection
    {
        $name = $name ?? \config('e-search.default_connection');

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
