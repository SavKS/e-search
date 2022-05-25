<?php

namespace Savks\ESearch\Support\Helpers;

use Closure;
use Illuminate\Database\Query\Builder as QueryBuilder;

use Illuminate\Database\Eloquent\{
    Relations\Relation,
    Builder as EloquentBuilder,
    SoftDeletes,
    SoftDeletingScope
};
use Illuminate\Support\{
    Arr,
    Collection
};

trait AsDefaultEloquentResource
{
    /**
     * @return EloquentBuilder|QueryBuilder|Relation
     */
    abstract protected function defaultQuery(): Relation|EloquentBuilder|QueryBuilder;

    /**
     * @return string|null
     */
    public function defaultChunkField(): ?string
    {
        return null;
    }

    /**
     * @param array $criteria
     * @return EloquentBuilder|QueryBuilder|Relation
     */
    protected function defaultSeedQuery(array $criteria): Relation|EloquentBuilder|QueryBuilder
    {
        return $this->defaultQuery();
    }

    /**
     * @param array $criteria
     * @return EloquentBuilder|QueryBuilder|Relation
     */
    protected function defaultCleanQuery(array $criteria): Relation|EloquentBuilder|QueryBuilder
    {
        return $this->defaultQuery();
    }

    /**
     * @return EloquentBuilder|QueryBuilder|Relation
     */
    protected function defaultMappingQuery(): Relation|EloquentBuilder|QueryBuilder
    {
        return $this->defaultQuery([]);
    }

    /**
     * @param array|null $ids
     * @param int $limit
     * @param Closure $callback
     * @param Closure $resolveCount
     * @param array $criteria
     */
    public function prepareSeed(
        ?array $ids,
        int $limit,
        Closure $callback,
        Closure $resolveCount,
        array $criteria = []
    ): void {
        $query = $this->defaultSeedQuery($criteria);

        if ($ids !== null) {
            $query->whereKey($ids);
        }

        $resolveCount(
            $query->count()
        );

        $defaultChunkField = $this->defaultChunkField();

        if ($defaultChunkField) {
            $query->chunkById(
                $limit,
                $callback,
                $this->defaultChunkField()
            );
        } else {
            $query->chunk($limit, $callback);
        }
    }

    /**
     * @param array|null $ids
     * @param int $limit
     * @param Closure $callback
     * @param Closure $resolveCount
     * @param array $criteria
     */
    public function prepareClean(
        ?array $ids,
        int $limit,
        Closure $callback,
        Closure $resolveCount,
        array $criteria = []
    ): void {
        $query = $this->defaultCleanQuery($criteria);

        if ($query instanceof EloquentBuilder) {
            $traits = class_uses(
                $query->getModel()
            );

            $hasSoftDelete = \in_array($traits, SoftDeletes::class, true);
        } else {
            $hasSoftDelete = false;
        }

        if ($hasSoftDelete) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        if ($ids !== null) {
            $query->whereKey($ids);
        } elseif ($hasSoftDelete) {
            $query->whereNotNull('deleted_at');
        }

        $resolveCount(
            $query->count()
        );

        $defaultChunkField = $this->defaultChunkField();

        if ($defaultChunkField) {
            $query->chunkById(
                $limit,
                $callback,
                $this->defaultChunkField()
            );
        } else {
            $query->chunk($limit, $callback);
        }
    }

    /**
     * @param array $result
     * @param Closure|null $resolver
     * @return array|Collection
     */
    public function mapTo(array $result, ?Closure $resolver): array|Collection
    {
        $query = $this->defaultMappingQuery();

        $ids = Arr::pluck($result['hits']['hits'], '_source.id');

        if ($resolver) {
            $resolver($query);
        }

        return $query
            ->findMany($ids)
            ->sortBy(function ($entity) use ($ids) {
                $index = array_search(
                    $entity->getKey(),
                    $ids,
                    true
                );

                return $index === false ? 99999 : $index;
            })
            ->values();
    }
}
