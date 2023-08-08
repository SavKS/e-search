<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use BackedEnum;
use Savks\ESearch\Builder\DSL\TermCondition;

trait HasTerm
{
    use AsConditionHelper;

    public function term(string $field, float|int|string|bool|BackedEnum $value): static
    {
        return $this->addCondition(
            new TermCondition($field, $value)
        );
    }
}
