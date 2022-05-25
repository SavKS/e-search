<?php

namespace Savks\ESearch\Support;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

use Savks\ESearch\Commands\{
    Clear,
    Seed,
    Sync
};

class ResourceRunner
{
    /**
     * @var class-string<MutableResource>
     */
    protected readonly string $mutableResourceFQN;

    /**
     * @param string $mutableResourceFQN
     */
    public function __construct(string $mutableResourceFQN)
    {
        if (! \is_subclass_of($mutableResourceFQN, MutableResource::class)) {
            throw new RuntimeException(
                'The runner only works with resources inherited from "' . MutableResource::class . '"'
            );
        }
        $this->mutableResourceFQN = $mutableResourceFQN;
    }

    /**
     * @param array $args
     * @param array|null $criteria
     */
    public function seed(array $args = [], array $criteria = null): void
    {
        if (! isset($args['resource'])) {
            $args['--resource'] = $this->mutableResourceFQN;
        }

        if (! empty($criteria)) {
            $args['--criteria'] = json_encode($criteria);
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
            $args['--resource'] = $this->mutableResourceFQN;
        }

        if (! empty($criteria)) {
            $args['--criteria'] = json_encode($criteria);
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
            $args['--resource'] = $this->mutableResourceFQN;
        }

        if (! empty($criteria)) {
            $args['--criteria'] = json_encode($criteria);
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
            $args['--resource'] = $this->mutableResourceFQN;
        }

        if (! empty($criteria)) {
            $args['--criteria'] = json_encode($criteria);
        }

        $args['--truncate'] = true;
        $args['--force'] = true;

        Artisan::call(Sync::class, $args);
    }
}
