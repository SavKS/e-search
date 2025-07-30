<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use BackedEnum;
use Savks\ESearch\Builder\DSL\TermsCondition;

trait HasTerms
{
    use AsConditionHelper;

    /**
     * @param array<float|int|string|bool|BackedEnum> $values
     */
    public function terms(string $field, array $values): static
    {
        return $this->addCondition(
            new TermsCondition($field, $values)
        );
    }
}
