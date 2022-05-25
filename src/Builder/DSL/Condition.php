<?php

namespace Savks\ESearch\Builder\DSL;

abstract class Condition
{
    /**
     * @return bool
     */
    abstract public function isEmpty(): bool;

    /**
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * @param int $flags
     * @return string
     */
    public function toJson(int $flags = 0): string
    {
        return \json_encode(
            $this->toArray(),
            $flags
        );
    }
}
