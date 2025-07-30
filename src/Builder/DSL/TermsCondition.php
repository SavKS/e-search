<?php

namespace Savks\ESearch\Builder\DSL;

use BackedEnum;

class TermsCondition extends Condition
{
    /**
     * @param array<float|int|string|bool|BackedEnum> $values
     */
    public function __construct(
        protected readonly string $field,
        protected readonly array $values
    ) {
    }

    public function isEmpty(): bool
    {
        return false;
    }

    /**
     * @return array{
     *     terms: array<string, array<float|int|string|bool>>
     * }
     */
    public function toArray(): array
    {
        $values = [];

        foreach ($this->values as $value) {
            $values[] = $value instanceof BackedEnum ? $value->value : $value;
        }

        return [
            'terms' => [
                $this->field => $values,
            ],
        ];
    }
}
