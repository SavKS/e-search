<?php

namespace Savks\ESearch\Builder\DSL;

interface Queryable
{
    public function toQuery(): Query;
}
