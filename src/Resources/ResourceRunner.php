<?php

namespace Savks\ESearch\Resources;

use Illuminate\Support\Arr;
use LogicException;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Support\MutableResource;
use Savks\ESearch\Support\RequestConfig;

/**
 * @template TResource of MutableResource
 */
class ResourceRunner
{
    protected Client $client;

    /**
     * @param TResource $resource
     */
    public function __construct(
        public readonly MutableResource $resource,
        ?string $connection = null
    ) {
        $this->client = new Client($connection);
    }

    public function indexExists(): bool
    {
        return $this->client->indexExists($this->resource);
    }

    /**
     * @param array<string|int>|string $ids
     */
    public function purge(array|string $ids): void
    {
        $this->client->bulkDelete(
            $this->resource,
            Arr::wrap($ids)
        );
    }

    /**
     * @param array<string|int>|string $ids
     */
    public function purgeSync(array|string $ids): void
    {
        $this->client->withConfig(
            (new RequestConfig())->refresh(),
            function () use ($ids) {
                $this->purge($ids);
            }
        );
    }

    /**
     * @param array<string|int>|string|null $ids
     * @param array<string, mixed> $criteria
     */
    public function push(array|string|null $ids = null, array $criteria = [], int $limit = 100): void
    {
        $ids = $ids === null ? null : Arr::wrap($ids);

        $this->resource->prepareSeed(
            $ids,
            $ids !== null ? count($ids) : $limit,
            function (iterable $items) use ($ids) {
                if ($ids !== null && iterator_count($items) > count($ids)) {
                    throw new LogicException('The number of items is greater than the specified number of ids');
                }

                $documents = [];

                foreach ($items as $item) {
                    $preparedDocuments = $this->resource->prepareDocuments($item);

                    if ($preparedDocuments !== null) {
                        $documents[] = $preparedDocuments;
                    }
                }

                if ($documents) {
                    $this->client->bulkSave(
                        $this->resource,
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

    /**
     * @param array<string|int>|string|null $ids
     * @param array<string, mixed> $criteria
     */
    public function pushSync(array|string|null $ids = null, array $criteria = [], int $limit = 100): void
    {
        $this->client->withConfig(
            (new RequestConfig())->refresh(),
            function () use ($ids, $criteria, $limit) {
                $this->push($ids, $criteria, $limit);
            }
        );
    }
}
