<?php

namespace Savks\ESearch\Support\Resources;

use Illuminate\Database\Query\Builder as QueryBuilder;

use Illuminate\Database\Eloquent\{
    Relations\Relation,
    Builder as EloquentBuilder
};

trait AsDefaultEloquentResource
{
    use SeedFromEloquent;
    use MapFromEloquent;

    /**
     * @return EloquentBuilder|QueryBuilder|Relation
     */
    abstract protected function defaultQuery(): Relation|EloquentBuilder|QueryBuilder;

    /**
     * @param array $criteria
     * @return EloquentBuilder|QueryBuilder|Relation
     */
    protected function defaultSeedQuery(array $criteria): Relation|EloquentBuilder|QueryBuilder
    {
        return $this->defaultQuery();
    }

    /**
     * @return EloquentBuilder|QueryBuilder|Relation
     */
    protected function defaultMappingQuery(): Relation|EloquentBuilder|QueryBuilder
    {
        return $this->defaultQuery();
    }
}
