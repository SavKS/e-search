<?php

namespace Savks\ESearch\Support\Resources;

use Illuminate\Database\Query\Builder as QueryBuilder;

use Illuminate\Database\Eloquent\{
    Relations\Relation,
    Builder as EloquentBuilder
};

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
