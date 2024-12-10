<?php

namespace Savks\ESearch\Support\Resources;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait SeedFromEloquent
{
    protected function defaultChunkField(): ?string
    {
        return null;
    }

    abstract protected function defaultSeedQuery(array $criteria): Relation|EloquentBuilder|QueryBuilder;

    public function prepareSeedChunk(EloquentCollection $items): iterable
    {
        return $items;
    }

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
                function (EloquentCollection $items) use ($callback) {
                    $callback(
                        $this->prepareSeedChunk($items)
                    );
                },
                $this->defaultChunkField()
            );
        } else {
            $query->chunk($limit, function (EloquentCollection $items) use ($callback) {
                $callback(
                    $this->prepareSeedChunk($items)
                );
            });
        }
    }
}
