<?php

namespace Savks\ESearch\Support;

use Illuminate\Support\Str;
use Savks\ESearch\Builder\Builder;
use Savks\ESearch\Elasticsearch\Client;

abstract class Resource
{
    protected Config $config;

    protected ?string $indexName = null;

    final public function __construct()
    {
        //
    }

    public static function name(): string
    {
        $classname = class_basename(static::class);

        return Str::snake(
            preg_replace('/Resource$/', '', $classname)
        );
    }

    public function indexName(): string
    {
        return $this->indexName ?? static::name();
    }

    public function useIndex(string $name): static
    {
        $this->indexName = $name;

        return $this;
    }

    public function getConnectionName(): ?string
    {
        return null;
    }

    public function documentIdBy(): string
    {
        return 'id';
    }

    abstract public static function configure(Config $config): void;

    public function config(): Config
    {
        if (! isset($this->config)) {
            $this->config = new Config();

            static::configure($this->config);
        }

        return $this->config;
    }

    public static function connection(string $name): Builder
    {
        return new Builder(
            new static(),
            $name
        );
    }

    public static function query(): Builder
    {
        return new Builder(
            new static()
        );
    }

    public static function newClient(?string $connection = null): Client
    {
        return new Client(
            $connection ?? (new static())->getConnectionName()
        );
    }
}
