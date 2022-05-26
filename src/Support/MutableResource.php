<?php

namespace Savks\ESearch\Support;

use Closure;
use ESearch;
use Illuminate\Http\Resources\MissingValue;
use LogicException;
use Savks\ESearch\Builder\DSL\Query;
use Savks\ESearch\Exceptions\EmptyQuery;
use Savks\ESearch\Resources\ResourceRunner;
use Savks\ESearch\Updates\Updates;

use Illuminate\Support\{
    Arr,
    Collection
};

abstract class MutableResource extends Resource
{
    /**
     * @return array
     */
    public function prepareMapping(): array
    {
        $mapping = $this->mapping();
        $properties = [];

        foreach ($mapping['properties'] ?? [] as $key => $value) {
            if (! $value instanceof MissingValue) {
                $properties[$key] = $value;
            }
        }

        $mapping['properties'] = $properties;

        return $mapping;
    }

    /**
     * @param mixed $entity
     * @return array
     */
    public function prepareDocuments(mixed $entity): array
    {
        $data = $this->buildDocument($entity);

        if (Arr::isList($data)) {
            $result = [];

            foreach ($data as $item) {
                $document = [];

                foreach ($item as $key => $value) {
                    if ($value instanceof MissingValue) {
                        continue;
                    }

                    $document[$key] = $value;
                }

                $result[] = $document;
            }

            return $result;
        }

        $document = [];

        foreach ($data as $key => $value) {
            if ($value instanceof MissingValue) {
                continue;
            }

            $document[$key] = $value;
        }

        return [$document];
    }

    /**
     * @param array|null $ids
     * @param int $limit
     * @param Closure $callback
     * @param Closure $resolveCount
     * @param array $criteria
     */
    abstract public function prepareSeed(
        ?array $ids,
        int $limit,
        Closure $callback,
        Closure $resolveCount,
        array $criteria = []
    ): void;

    /**
     * @param array|null $ids
     * @param int $limit
     * @param Closure $callback
     * @param Closure $resolveCount
     * @param array $criteria
     */
    abstract public function prepareClean(
        ?array $ids,
        int $limit,
        Closure $callback,
        Closure $resolveCount,
        array $criteria = []
    ): void;

    /**
     * @param array|string|null $ids
     * @param array $criteria
     * @param int $limit
     */
    public static function push(array|string $ids = null, array $criteria = [], int $limit = 100): void
    {
        $ids = $ids === null ? null : Arr::wrap($ids);

        $instance = new static();

        $instance->prepareSeed(
            $ids,
            $ids !== null ? \count($ids) : $limit,
            function (Collection $items) use ($instance, $ids) {
                if ($ids !== null && $items->count() > count($ids)) {
                    throw new LogicException('The number of items is greater than the specified number of ids');
                }

                $documents = [];

                foreach ($items as $item) {
                    $documents[] = $instance->prepareDocuments($item);
                }

                ESearch::bulkSave(
                    $instance,
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
    public static function pushSync(array|string $ids = null, array $criteria = [], int $limit = 100): void
    {
        ESearch::withConfig(
            (new RequestConfig())->refresh(),
            function () use ($ids, $criteria, $limit) {
                static::push($ids, $criteria, $limit);
            }
        );
    }

    /**
     * @param array|string|null $ids
     * @param array $criteria
     * @param int $limit
     */
    public static function purge(array|string $ids = null, array $criteria = [], int $limit = 100): void
    {
        $ids = $ids === null ? null : Arr::wrap($ids);

        $instance = new static();

        $instance->prepareClean(
            $ids,
            $ids !== null ? \count($ids) : $limit,
            function (Collection|Query $predicate) use ($instance, $ids) {
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

                    ESearch::bulkDelete(
                        $instance,
                        $items->pluck(
                            $instance->documentIdBy()
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
     */
    public static function purgeSync(array|string $ids = null, array $criteria = [], int $limit = 100): void
    {
        ESearch::withConfig(
            (new RequestConfig())->refresh(),
            function () use ($ids, $criteria, $limit) {
                static::purge($ids, $criteria, $limit);
            }
        );
    }

    /**
     * @param mixed $entity
     * @return array
     */
    abstract public function buildDocument(mixed $entity): array;

    /**
     * @return array
     */
    public function index(): array
    {
        return [];
    }

    /**
     * @return array
     */
    abstract public function mapping(): array;

    /**
     * @param Updates $updates
     * @return Updates
     */
    public function updates(Updates $updates): Updates
    {
        return $updates;
    }

    /**
     * @return \Savks\ESearch\Resources\ResourceRunner
     */
    public static function runner(): ResourceRunner
    {
        return new ResourceRunner(static::class);
    }
}
