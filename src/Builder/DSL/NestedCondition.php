<?php

namespace Savks\ESearch\Builder\DSL;

class NestedCondition extends Condition
{
    /**
     * @var string
     */
    protected string $path;

    /**
     * @var Query
     */
    protected Query $query;

    /**
     * @param string $path
     * @param Query $query
     */
    public function __construct(string $path, Query $query)
    {
        $this->path = $path;
        $this->query = $query;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->query->isEmpty();
    }

    /**
     * @return array
     */
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
