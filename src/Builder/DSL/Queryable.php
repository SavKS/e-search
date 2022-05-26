<?php

namespace Savks\ESearch\Builder\DSL;

interface Queryable
{
    /**
     * @return Query
     */
    public function toQuery(): Query;
}
