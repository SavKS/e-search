<?php

namespace Savks\ESearch\Builder;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Support\Str;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    ServerResponseException
};

class CountQuery
{
    /**
     * @var Builder
     */
    protected Builder $query;

    /**
     * @var bool
     */
    protected bool $withPerformanceTracking;

    /**
     * @param Builder $query
     * @param bool $withPerformanceTracking
     */
    public function __construct(Builder $query, bool $withPerformanceTracking = false)
    {
        $this->query = $query;
        $this->withPerformanceTracking = $withPerformanceTracking;
    }

    /**
     * @return ElasticsearchResponse
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws AuthenticationException
     */
    public function exec(): ElasticsearchResponse
    {
        $queryUniqId = Str::random();

        if ($this->withPerformanceTracking && \function_exists('clock')) {
            \clock()->event("ESearch: Count from \"{$this->query->resource::name()}\"", [
                'name' => $queryUniqId,
            ])->begin();
        }

        $result = $this->query->connection->client()->count([
            'index' => $this->query->indexName(),
            'body' => [
                'query' => $this->query->toBodyQuery(),
            ],
        ]);

        if ($this->withPerformanceTracking && \function_exists('clock')) {
            \clock()->event("ESearch: Count from \"{$this->query->resource::name()}\"", [
                'name' => $queryUniqId,
            ])->end();
        }

        return $result;
    }

    /**
     * @return int
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function resolve(): int
    {
        return $this->exec()['count'];
    }
}
