<?php

namespace Savks\ESearch\Support;

use Illuminate\Support\Str;
use Savks\ESearch\Builder\Builder;

abstract class Resource
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @return void
     */
    final public function __construct()
    {
        //
    }


    /**
     * @return string
     */
    public static function name(): string
    {
        $classname = class_basename(static::class);

        return Str::snake(
            preg_replace('/Resource$/', '', $classname)
        );
    }

    /**
     * @return string
     */
    public function prefixedIndexName(): string
    {
        return sprintf(
            '%s_%s',
            Str::snake(
                strtolower(
                    config('e-search.index_prefix')
                )
            ),
            $this->indexName()
        );
    }

    /**
     * @return string
     */
    public function indexName(): string
    {
        return static::name();
    }

    /**
     * @return string
     */
    public function documentIdBy(): string
    {
        return 'id';
    }

    /**
     * @param Config $config
     */
    abstract public static function configure(Config $config): void;

    /**
     * @return Config
     */
    public function config(): Config
    {
        if (! isset($this->config)) {
            $this->config = new Config();

            static::configure($this->config);
        }

        return $this->config;
    }

    /**
     * @return Builder
     */
    public static function query(): Builder
    {
        return new Builder(
            new static()
        );
    }
}
