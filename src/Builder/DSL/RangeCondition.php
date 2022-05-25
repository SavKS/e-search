<?php

namespace Savks\ESearch\Builder\DSL;

class RangeCondition extends Condition
{
    /**
     * @var string
     */
    protected string $field;

    /**
     * @var array
     */
    protected array $value;

    /**
     * @param string $field
     * @param array $value
     */
    public function __construct(string $field, array $value)
    {
        $this->field = $field;
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
        return [
            'range' => [
                $this->field => $this->value,
            ],
        ];
    }
}
