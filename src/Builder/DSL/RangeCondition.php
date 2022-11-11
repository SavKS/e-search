<?php

namespace Savks\ESearch\Builder\DSL;

class RangeCondition extends Condition
{
    public function __construct(
        protected readonly string $field,
        protected readonly array $value
    ) {
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return [
            'range' => [
                $this->field => $this->value,
            ],
        ];
    }
}
