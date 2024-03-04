<?php

namespace Savks\ESearch\Builder\DSL;

abstract class Condition
{
    abstract public function isEmpty(): bool;

    abstract public function toArray(): array;

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode(
            $this->toArray(),
            $flags
        );
    }
}
