<?php

namespace Savks\ESearch\Support\Resources;

use Closure;
use Illuminate\Database\Query\Builder as QueryBuilder;

use Illuminate\Database\Eloquent\{
    Relations\Relation,
    Builder as EloquentBuilder
};

trait SeedFromEloquent
{
    /**
     * @return string|null
     */
    protected function defaultChunkField(): ?string
    {
        return null;
    }

    /**
     * @param array $criteria
     * @return EloquentBuilder|QueryBuilder|Relation
     */
    abstract protected function defaultSeedQuery(array $criteria): Relation|EloquentBuilder|QueryBuilder;

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
}
