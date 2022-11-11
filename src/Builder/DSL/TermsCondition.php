<?php

namespace Savks\ESearch\Builder\DSL;

class TermsCondition extends Condition
{
    public function __construct(
        protected readonly string $field,
        protected readonly array $values
    ) {
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return [
            'terms' => [
                $this->field => $this->values,
            ],
        ];
    }
}
