<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\TermsCondition;

trait HasTerms
{
    use AsConditionHelper;

    public function terms(string $field, array $values): static
    {
        return $this->addCondition(
            new TermsCondition($field, $values)
        );
    }
}
