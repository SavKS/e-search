<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\RangeCondition;

trait HasRange
{
    use AsConditionHelper;

    public function range(string $field, array $data): static
    {
        return $this->addCondition(
            new RangeCondition($field, $data)
        );
    }
}
