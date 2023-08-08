<?php

namespace Savks\ESearch\Builder\DSL;

use BackedEnum;

class TermCondition extends Condition
{
    public function __construct(
        protected readonly string $field,
        protected readonly float|int|string|bool|BackedEnum $value
    ) {
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return [
            'term' => [
                $this->field => $this->value instanceof BackedEnum ? $this->value->value : $this->value,
            ],
        ];
    }
}
