<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\{
    Queryable,
    RawCondition
};

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
