<?php

namespace Savks\ESearch\Support\Resources;

use Closure;
use Illuminate\Database\Query\Builder as QueryBuilder;

use Illuminate\Database\Eloquent\{
    Relations\Relation,
    Builder as EloquentBuilder
};
use Illuminate\Support\{
    Arr,
    Collection
};

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
