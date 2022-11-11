<?php

namespace Savks\ESearch\Builder\DSL;

class RawCondition extends Condition
{
    public function __construct(protected readonly array $value)
    {
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return $this->value;
    }
}
