<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\NestedCondition;
use Savks\ESearch\Builder\DSL\Query;

trait HasNested
{
    use AsConditionHelper;

    public function nested(string $path, callable|Query $predicate): static
    {
        $query = $predicate instanceof Query ? $predicate : new Query();

        $condition = new NestedCondition($path, $query);

        if (is_callable($predicate)) {
            $predicate($query);
        }

        return $this->addCondition($condition);
    }
}
