<?php

namespace Savks\ESearch\Support\Resources;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait AsDefaultEloquentResource
{
    use MapFromEloquent;
    use SeedFromEloquent;

    abstract protected function defaultQuery(): Relation|EloquentBuilder|QueryBuilder;

    protected function defaultSeedQuery(array $criteria): Relation|EloquentBuilder|QueryBuilder
    {
        return $this->defaultQuery();
    }

    protected function defaultMappingQuery(): Relation|EloquentBuilder|QueryBuilder
    {
        return $this->defaultQuery();
    }
}
