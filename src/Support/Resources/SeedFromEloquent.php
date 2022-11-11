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
    protected function defaultChunkField(): ?string
    {
        return null;
    }

    abstract protected function defaultSeedQuery(array $criteria): Relation|EloquentBuilder|QueryBuilder;

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
