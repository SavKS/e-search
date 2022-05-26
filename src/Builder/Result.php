<?php

namespace Savks\ESearch\Builder;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Savks\ESearch\Support\Resource;

class Result
{
    /**
     * @var array
     */
    public readonly array $data;

    /**
     * @var Collection
     */
    public readonly Collection $items;

    /**
     * @var LengthAwarePaginator|null
     */
    public readonly ?LengthAwarePaginator $paginator;

    /**
     * @var Resource
     */
    public readonly Resource $resource;

    /**
     * @var ElasticsearchResponse
     */
    public readonly ElasticsearchResponse $response;

    /**
     * @param array $data
     * @param Collection $items
     * @param LengthAwarePaginator|null $paginator
     * @param Resource $resource
     * @param ElasticsearchResponse $response
     */
    public function __construct(
        array $data,
        Collection $items,
        ?LengthAwarePaginator $paginator,
        Resource $resource,
        ElasticsearchResponse $response
    ) {
        $this->data = $data;
        $this->items = $items;
        $this->paginator = $paginator;
        $this->resource = $resource;
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data['hits']['hits']);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * @return array
     */
    public function ids(): array
    {
        return $this->items->pluck(
            $this->resource->documentIdBy()
        )->all();
    }

    /**
     * @return int|null
     */
    public function total(): ?int
    {
        return $this->data['hits']['total']['value'] ?: null;
    }

    /**
     * @return array
     */
    public function hits(): array
    {
        return $this->data['hits']['hits'];
    }

    /**
     * @return array
     */
    public function customAggregations(): array
    {
        return $this->data['aggregations'] ?? [];
    }

    /**
     * @param string $name
     * @return array|null
     */
    public function customAggregation(string $name): ?array
    {
        return $this->data['aggregations'][$name] ?? null;
    }
}
