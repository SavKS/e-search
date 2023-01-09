<?php

namespace Savks\ESearch\Resources;

use LogicException;
use Savks\ESearch\Elasticsearch\Client;

use Illuminate\Support\{
    Arr,
    Collection
};
use Savks\ESearch\Support\{
    MutableResource,
    RequestConfig
};

class ResourceRunner
{
    protected Client $manager;

    public function __construct(
        protected readonly MutableResource $mutableResource,
        string $connection = null
    ) {
        $this->manager = new Client($connection);
    }

    public function purge(array|string $ids): void
    {
        $this->manager->bulkDelete(
            $this->mutableResource,
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

    public function push(array|string $ids = null, array $criteria = [], int $limit = 100): void
    {
        $ids = $ids === null ? null : Arr::wrap($ids);

        $this->mutableResource->prepareSeed(
            $ids,
            $ids !== null ? \count($ids) : $limit,
            function (Collection $items) use ($ids) {
                if ($ids !== null && $items->count() > count($ids)) {
                    throw new LogicException('The number of items is greater than the specified number of ids');
                }

                $documents = [];

                foreach ($items as $item) {
                    $documents[] = $this->mutableResource->prepareDocuments($item);
                }

                $this->manager->bulkSave(
                    $this->mutableResource,
                    \array_merge(...$documents)
                );
            },
            function (int $count) {
                //
            },
            $criteria
        );
    }

    public function pushSync(array|string $ids = null, array $criteria = [], int $limit = 100): void
    {
        $this->manager->withConfig(
            (new RequestConfig())->refresh(),
            function () use ($ids, $criteria, $limit) {
                $this->push($ids, $criteria, $limit);
            }
        );
    }
}
