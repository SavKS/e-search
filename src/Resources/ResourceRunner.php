<?php

namespace Savks\ESearch\Resources;

use LogicException;
use Savks\ESearch\Builder\DSL\Query;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Exceptions\EmptyQuery;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    ServerResponseException
};
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
    /**
     * @var MutableResource
     */
    protected readonly MutableResource $mutableResource;

    /**
     * @var Client
     */
    protected Client $manager;

    /**
     * @param MutableResource $mutableResource
     * @param string|null $connection
     */
    public function __construct(MutableResource $mutableResource, string $connection = null)
    {
        $this->mutableResource = $mutableResource;

        $this->manager = new Client($connection);
    }

    /**
     * @param array|string|null $ids
     * @param array $criteria
     * @param int $limit
     * @return void
     */
    public function purge(array|string $ids = null, array $criteria = [], int $limit = 100): void
    {
        $ids = $ids === null ? null : Arr::wrap($ids);

        $this->mutableResource->prepareClean(
            $ids,
            $ids !== null ? \count($ids) : $limit,
            function (Collection|Query $predicate) use ($ids) {
                if ($predicate instanceof Query) {
                    $query = $predicate;

                    if ($query->isEmpty()) {
                        throw new EmptyQuery('Delete query is empty');
                    }
                } else {
                    $items = $predicate;

                    if ($ids !== null && $items->count() > count($ids)) {
                        throw new LogicException('The number of items is greater than the specified number of ids');
                    }

                    $this->manager->bulkDelete(
                        $this->mutableResource,
                        $items->pluck(
                            $this->mutableResource->documentIdBy()
                        )
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
     * @param array|string|null $ids
     * @param array $criteria
     * @param int $limit
     * @return void
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function purgeSync(
        array|string $ids = null,
        array $criteria = [],
        int $limit = 100
    ): void {
        $this->manager->withConfig(
            (new RequestConfig())->refresh(),
            function () use ($ids, $criteria, $limit) {
                $this->purge($ids, $criteria, $limit);
            }
        );
    }

    /**
     * @param array|string|null $ids
     * @param array $criteria
     * @param int $limit
     * @return void
     */
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

    /**
     * @param array|string|null $ids
     * @param array $criteria
     * @param int $limit
     * @return void
     */
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
