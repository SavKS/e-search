<?php

namespace Savks\ESearch\Builder\DSL;

class ExistsCondition extends Condition
{
    /**
     * @var string
     */
    protected string $field;

    /**
     * @param string $field
     */
    public function __construct(string $field)
    {
        $this->field = $field;
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
            'exists' => [
                'field' => $this->field,
            ],
        ];
    }
}
