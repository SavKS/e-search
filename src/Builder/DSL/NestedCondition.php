<?php

namespace Savks\ESearch\Builder\DSL;

class NestedCondition extends Condition
{
    public function __construct(
        protected readonly string $path,
        protected readonly Query $query
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->query->isEmpty();
    }

    public function toArray(): array
    {
        return [
            'nested' => [
                'path' => $this->path,
                'query' => $this->query->toArray(),
            ],
        ];
    }
}
