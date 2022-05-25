<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\ExistsCondition;

trait HasExists
{
    use AsConditionHelper;

    /**
     * @param string $field
     * @return $this
     */
    public function exists(string $field): static
    {
        return $this->addCondition(
            new ExistsCondition($field)
        );
    }
}
