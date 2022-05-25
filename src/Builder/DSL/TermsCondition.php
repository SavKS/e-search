<?php

namespace Savks\ESearch\Builder\DSL;

class TermsCondition extends Condition
{
    /**
     * @var string
     */
    protected string $field;

    /**
     * @var array
     */
    protected array $values;

    /**
     * @param string $field
     * @param array $values
     */
    public function __construct(string $field, array $values)
    {
        $this->field = $field;
        $this->values = $values;
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
            'terms' => [
                $this->field => $this->values,
            ],
        ];
    }
}
