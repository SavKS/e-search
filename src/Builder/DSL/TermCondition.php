<?php

namespace Savks\ESearch\Builder\DSL;

class TermCondition extends Condition
{
    /**
     * @var string
     */
    protected string $field;

    /**
     * @var string|int|float|bool
     */
    protected string|int|float|bool $value;

    /**
     * @param string $field
     * @param float|int|string|bool $value
     */
    public function __construct(string $field, float|int|string|bool $value)
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
            'term' => [
                $this->field => $this->value,
            ],
        ];
    }
}
