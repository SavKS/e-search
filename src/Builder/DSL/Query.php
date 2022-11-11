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

    protected ?Condition $condition;

    protected function addCondition(Condition $condition): static
    {
        if (isset($this->condition)) {
            throw new InvalidOperation('DSL query already has condition');
        }

        $this->condition = $condition;

        return $this;
    }

    public function isEmpty(): bool
    {
        return ! isset($this->condition) || $this->condition->isEmpty();
    }

    public function toArray(): array
    {
        if ($this->isEmpty()) {
            throw new EmptyQuery('DSL query is empty');
        }

        return $this->condition->toArray();
    }

    public function toJson(int $flags = 0): string
    {
        return \json_encode(
            $this->toArray(),
            $flags
        );
    }
}
