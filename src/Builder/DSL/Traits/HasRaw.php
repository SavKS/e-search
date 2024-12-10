<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\Queryable;
use Savks\ESearch\Builder\DSL\RawCondition;

trait HasRaw
{
    use AsConditionHelper;

    public function raw(array|Queryable $value): static
    {
        return $this->addCondition(
            new RawCondition($value)
        );
    }
}
