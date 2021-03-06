<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\RawCondition;

trait HasRaw
{
    use AsConditionHelper;

    /**
     * @param array $value
     * @return $this
     */
    public function raw(array $value): static
    {
        return $this->addCondition(
            new RawCondition($value)
        );
    }
}
