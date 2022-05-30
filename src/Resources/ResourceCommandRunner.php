<?php

namespace Savks\ESearch\Resources;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

use Savks\ESearch\Commands\{
    Clear,
    Seed,
    Sync
};
use Savks\ESearch\Support\MutableResource;

class ResourceCommandRunner
{
    /**
     * @var MutableResource
     */
    protected readonly MutableResource $mutableResource;

    /**
     * @var string|null
     */
    protected ?string $connection;

    /**
     * @param MutableResource $mutableResource
     * @param string|null $connection
     */
    public function __construct(MutableResource $mutableResource, string $connection = null)
    {
        $this->mutableResource = $mutableResource;
        $this->connection = $connection;
    }

    /**
     * @param array $args
     * @param array|null $criteria
     */
    public function seed(array $args = [], array $criteria = null): void
    {
        if (! isset($args['resource'])) {
            $args['--resource'] = \get_class($this->mutableResource);
        }

        if (! empty($criteria)) {
            $args['--criteria'] = json_encode($criteria);
        }

        if ($this->connection) {
            $args['--connection'] = $this->connection;
        }

        $args['--force'] = true;

        Artisan::call(Seed::class, $args);
    }

    /**
     * @param array $args
     * @param array|null $criteria
     */
    public function clear(array $args = [], array $criteria = null): void
    {
        if (! isset($args['--resource'])) {
            $args['--resource'] = \get_class($this->mutableResource);
        }

        if (! empty($criteria)) {
            $args['--criteria'] = json_encode($criteria);
        }

        if ($this->connection) {
            $args['--connection'] = $this->connection;
        }

        $args['--force'] = true;

        Artisan::call(Clear::class, $args);
    }

    /**
     * @param array $args
     * @param array|null $criteria
     */
    public function sync(array $args = [], array $criteria = null): void
    {
        if (! isset($args['--resource'])) {
            $args['--resource'] = \get_class($this->mutableResource);
        }

        if (! empty($criteria)) {
            $args['--criteria'] = json_encode($criteria);
        }

        if ($this->connection) {
            $args['--connection'] = $this->connection;
        }

        $args['--force'] = true;

        Artisan::call(Sync::class, $args);
    }

    /**
     * @param array $args
     * @param array|null $criteria
     */
    public function syncWithTruncate(array $args = [], array $criteria = null): void
    {
        if (! isset($args['--resource'])) {
            $args['--resource'] = \get_class($this->mutableResource);
        }

        if (! empty($criteria)) {
            $args['--criteria'] = json_encode($criteria);
        }

        if ($this->connection) {
            $args['--connection'] = $this->connection;
        }

        $args['--truncate'] = true;
        $args['--force'] = true;

        Artisan::call(Sync::class, $args);
    }
}
