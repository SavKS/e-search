<?php

namespace Savks\ESearch\Resources;

use Illuminate\Support\Arr;
use Savks\ESearch\Elasticsearch\Client;

use Savks\ESearch\Support\{
    NativeMutableResource,
    RequestConfig
};

class NativeResourceRunner
{
    protected Client $manager;

    public function __construct(
        protected readonly NativeMutableResource $resource,
        string $connection = null
    ) {
        $this->manager = new Client($connection);
    }

    public function purge(array|string $ids): void
    {
        $this->manager->bulkDelete(
            $this->resource,
            Arr::wrap($ids)
        );
    }

    public function purgeSync(array|string $ids): void
    {
        $this->manager->withConfig(
            (new RequestConfig())->refresh(),
            function () use ($ids) {
                $this->purge($ids);
            }
        );
    }

    public function push(array $documents): void
    {
        $this->manager->bulkSave(
            $this->resource,
            array_is_list($documents) ? $documents : [$documents]
        );
    }

    public function pushSync(array $documents): void
    {
        $this->manager->withConfig(
            (new RequestConfig())->refresh(),
            function () use ($documents) {
                $this->push($documents);
            }
        );
    }
}
