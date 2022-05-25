<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\RangeCondition;

trait HasRange
{
    use AsConditionHelper;

    /**
     * @param string $field
     * @param array $data
     * @return $this
     */
    public function range(string $field, array $data): static
    {
        return $this->addCondition(
            new RangeCondition($field, $data)
        );
    }
}
