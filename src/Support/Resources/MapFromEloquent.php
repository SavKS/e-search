<?php

namespace Savks\ESearch\Support\Resources;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait MapFromEloquent
{
    abstract protected function defaultMappingQuery(): Relation|EloquentBuilder|QueryBuilder;

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
