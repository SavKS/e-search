<?php

namespace Savks\ESearch\Builder\DSL;

class TermCondition extends Condition
{
    public function __construct(
        protected readonly string $field,
        protected readonly float|int|string|bool $value
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
                $this->field => $this->value,
            ],
        ];
    }
}
