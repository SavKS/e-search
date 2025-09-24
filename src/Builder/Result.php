<?php

namespace Savks\ESearch\Builder;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Savks\ESearch\Support\Resource;

/**
 * @template TValue
 * @template TPaginated as bool
 */
class Result
{
    /**
     * @param Collection<int, TValue> $items
     * @param (TPaginated is true ? LengthAwarePaginator<int, TValue> : null) $paginator
     */
    public function __construct(
        public readonly array $data,
        public readonly Collection $items,
        public readonly ?LengthAwarePaginator $paginator,
        public readonly Resource $resource,
        public readonly ElasticsearchResponse $response
    ) {
    }

    public function isEmpty(): bool
    {
        return empty($this->data['hits']['hits']);
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function ids(): array
    {
        return $this->items->pluck(
            $this->resource->documentIdBy()
        )->all();
    }

    public function total(): ?int
    {
        return $this->data['hits']['total']['value'] ?: null;
    }

    public function hits(): array
    {
        return $this->data['hits']['hits'];
    }

    public function customAggregations(): array
    {
        return $this->data['aggregations'] ?? [];
    }

    public function customAggregation(string $name): ?array
    {
        return $this->data['aggregations'][$name] ?? null;
    }
}
