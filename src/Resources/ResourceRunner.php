<?php

namespace Savks\ESearch\Resources;

use Illuminate\Support\Arr;
use LogicException;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Support\MutableResource;
use Savks\ESearch\Support\RequestConfig;

class ResourceRunner
{
    protected Client $manager;

    public function __construct(
        protected readonly MutableResource $mutableResource,
        ?string $connection = null
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

    public function push(array|string|null $ids = null, array $criteria = [], int $limit = 100): void
    {
        $ids = $ids === null ? null : Arr::wrap($ids);

        $this->mutableResource->prepareSeed(
            $ids,
            $ids !== null ? count($ids) : $limit,
            function (iterable $items) use ($ids) {
                if ($ids !== null && iterator_count($items) > count($ids)) {
                    throw new LogicException('The number of items is greater than the specified number of ids');
                }

                $documents = [];

                foreach ($items as $item) {
                    $preparedDocuments = $this->mutableResource->prepareDocuments($item);

                    if ($preparedDocuments !== null) {
                        $documents[] = $preparedDocuments;
                    }
                }

                if ($documents) {
                    $this->manager->bulkSave(
                        $this->mutableResource,
                        array_merge(...$documents)
                    );
                }
            },
            function (int $count) {
                //
            },
            $criteria
        );
    }

    public function pushSync(array|string|null $ids = null, array $criteria = [], int $limit = 100): void
    {
        $this->manager->withConfig(
            (new RequestConfig())->refresh(),
            function () use ($ids, $criteria, $limit) {
                $this->push($ids, $criteria, $limit);
            }
        );
    }
}
