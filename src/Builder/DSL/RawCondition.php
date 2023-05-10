<?php

namespace Savks\ESearch\Builder\DSL;

class RawCondition extends Condition
{
    public function __construct(protected readonly array|Queryable $value)
    {
    }

    public function isEmpty(): bool
    {
        return $this->value instanceof Queryable ? $this->value->toQuery()->isEmpty() : false;
    }

    public function toArray(): array
    {
        return $this->value instanceof Queryable ?
            $this->value->toQuery()->toArray() :
            $this->value;
    }
}
