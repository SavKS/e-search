<?php

namespace Savks\ESearch\Builder\DSL;

use Savks\ESearch\Builder\DSL\Traits\HasDefaultConditions;

use Savks\ESearch\Exceptions\{
    EmptyQuery,
    InvalidOperation
};

class Query
{
    use HasDefaultConditions;

    /**
     * @var Condition|null
     */
    protected ?Condition $condition;

    /**
     * @param Condition $condition
     * @return $this
     */
    protected function addCondition(Condition $condition): static
    {
        if (isset($this->condition)) {
            throw new InvalidOperation('DSL query already has condition');
        }

        $this->condition = $condition;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return ! isset($this->condition) || $this->condition->isEmpty();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        if ($this->isEmpty()) {
            throw new EmptyQuery('DSL query is empty');
        }

        return $this->condition->toArray();
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
