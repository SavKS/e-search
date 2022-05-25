<?php

namespace Savks\ESearch\Builder\DSL;

class RawCondition extends Condition
{
    /**
     * @var array
     */
    protected array $value;

    /**
     * @param array $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return false;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->value;
    }
}
