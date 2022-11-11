<?php

namespace Savks\ESearch\Builder\DSL;

class ExistsCondition extends Condition
{
    public function __construct(protected readonly string $field)
    {
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return [
            'exists' => [
                'field' => $this->field,
            ],
        ];
    }
}
