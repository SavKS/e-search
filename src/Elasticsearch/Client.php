<?php

namespace Savks\ESearch\Elasticsearch;

use Closure;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
use Savks\ESearch\Builder\DSL\Query;
use Savks\ESearch\Debug\PerformanceTracker;
use stdClass;

use Elastic\Elasticsearch\{
    Exception\AuthenticationException,
    Exception\ClientResponseException,
    Exception\MissingParameterException,
    Exception\ServerResponseException,
    Response\Elasticsearch as ElasticsearchResponse,
    Client as ElasticsearchClient
};
use Savks\ESearch\Support\{
    RequestConfig,
    RequestConfigContract,
    Resource
};

class Client
{
    /**
     * @var Connection
     */
    public readonly Connection $connection;

    /**
     * @var RequestConfigContract
     */
    protected RequestConfigContract $requestConfig;

    /**
     * @param string|null $connection
     */
    public function __construct(string $connection = null)
    {
        $this->connection = $connection ?
            \app(ConnectionsManager::class)->resolve($connection) :
            \app(ConnectionsManager::class)->resolveDefault();

        $this->requestConfig = new RequestConfig();
    }

    /**
     * @return ElasticsearchClient
     * @throws AuthenticationException
     */
    public function elasticsearchClient(): ElasticsearchClient
    {
        return $this->connection->client();
    }

    /**
     * @param Resource $resource
     * @param array $document
     * @return ElasticsearchResponse|Promise
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function save(Resource $resource, array $document): ElasticsearchResponse|Promise
    {
        $response = $this->measure(
            $resource,
            fn() => $this->connection->client()->index(
                $this->requestConfig->applyToRequest(RequestTypes::SAVE, [
                    'id' => $document[$resource->documentIdBy()],
                    'index' => $this->connection->resolveIndexName(
                        $resource->indexName()
                    ),
                    'body' => $document,
                ])
            )
        );

        $this->connection->errorsHandler()->processResponse(RequestTypes::SAVE, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @param iterable $documents
     * @return ElasticsearchResponse
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function bulkSave(Resource $resource, iterable $documents): ElasticsearchResponse
    {
        $params = [];

        foreach ($documents as $document) {
            $params['body'][] = [
                'index' => [
                    '_id' => $document[$resource->documentIdBy()],
                    '_index' => $this->connection->resolveIndexName(
                        $resource->indexName()
                    ),
                ],
            ];

            $params['body'][] = $document;
        }

        $response = $this->measure(
            $resource,
            fn() => $this->connection->client()->bulk(
                $this->requestConfig->applyToRequest(RequestTypes::BULK_SAVE, $params)
            )
        );

        $this->connection->errorsHandler()->processResponse(RequestTypes::BULK_SAVE, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @param int|string $id
     * @return ElasticsearchResponse
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function delete(Resource $resource, int|string $id): ElasticsearchResponse
    {
        $response = $this->measure(
            $resource,
            fn() => $this->connection->client()->delete(
                $this->requestConfig->applyToRequest(RequestTypes::DELETE, [
                    'id' => $id,
                    'index' => $this->connection->resolveIndexName(
                        $resource->indexName()
                    ),
                ])
            )
        );

        $this->connection->errorsHandler()->processResponse(RequestTypes::DELETE, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @param iterable $ids
     * @return ElasticsearchResponse
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function bulkDelete(Resource $resource, iterable $ids): ElasticsearchResponse
    {
        $params = [];

        foreach ($ids as $id) {
            $params['body'][] = [
                'delete' => [
                    '_id' => $id,
                    '_index' => $this->connection->resolveIndexName(
                        $resource->indexName()
                    ),
                ],
            ];
        }

        $response = $this->measure(
            $resource,
            fn() => $this->connection->client()->bulk(
                $this->requestConfig->applyToRequest(RequestTypes::BULK_DELETE, $params)
            )
        );

        $this->connection->errorsHandler()->processResponse(RequestTypes::BULK_DELETE, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @param Query|array $bodyQuery
     * @return ElasticsearchResponse|Promise
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function deleteByQuery(Resource $resource, Query|array $bodyQuery): ElasticsearchResponse|Promise
    {
        $response = $this->measure(
            $resource,
            fn() => $this->connection->client()->deleteByQuery(
                $this->requestConfig->applyToRequest(RequestTypes::DELETE_BY_QUERY, [
                    'index' => $this->connection->resolveIndexName(
                        $resource->indexName()
                    ),
                    'body' => [
                        'query' => $bodyQuery instanceof Query ? $bodyQuery->toArray() : $bodyQuery,
                    ],
                ])
            )
        );

        $this->connection->errorsHandler()->processResponse(RequestTypes::DELETE_BY_QUERY, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @return ElasticsearchResponse
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function truncate(Resource $resource): ElasticsearchResponse
    {
        $response = $this->measure(
            $resource,
            fn() => $this->connection->client()->deleteByQuery(
                $this->requestConfig->applyToRequest(RequestTypes::TRUNCATE, [
                    'index' => $this->connection->resolveIndexName(
                        $resource->indexName()
                    ),
                    'body' => [
                        'query' => [
                            'match_all' => new stdClass(),
                        ],
                    ],
                ])
            )
        );

        $this->connection->errorsHandler()->processResponse(RequestTypes::TRUNCATE, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @param array $request
     * @return ElasticsearchResponse
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function search(Resource $resource, array $request): ElasticsearchResponse
    {
        return $this->measure(
            $resource,
            fn() => $this->connection->client()->search([
                ...$request,

                'index' => $this->connection->resolveIndexName(
                    $resource->indexName()
                ),
            ])
        );
    }

    /**
     * @param Resource $resource
     * @param array|Query $bodyQuery
     * @return ElasticsearchResponse
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function count(Resource $resource, array|Query $bodyQuery): ElasticsearchResponse
    {
        return $this->measure(
            $resource,
            fn() => $this->connection->client()->count([
                'index' => $this->connection->resolveIndexName(
                    $resource->indexName()
                ),
                'body' => [
                    'query' => $bodyQuery instanceof Query ? $bodyQuery->toArray() : $bodyQuery,
                ],
            ])
        );
    }

    /**
     * @param Closure|RequestConfigContract $config
     * @param Closure $actionsCallback
     * @return self
     */
    public function withConfig(RequestConfigContract|Closure $config, Closure $actionsCallback): self
    {
        $oldConfig = $this->requestConfig;

        try {
            if ($config instanceof Closure) {
                $this->requestConfig = $config($oldConfig);
            } else {
                $this->requestConfig = $config;
            }

            $actionsCallback();
        } finally {
            $this->requestConfig = $oldConfig;
        }

        return $this;
    }

    /**
     * @param Resource $resource
     * @param Closure $callback
     * @return ElasticsearchResponse
     */
    protected function measure(Resource $resource, Closure $callback): ElasticsearchResponse
    {
        $stopMeasure = $this->connection->isTrackPerformanceEnabled ?
            \app(PerformanceTracker::class)->runMeasure($resource) :
            null;

        $result = $callback();

        if ($stopMeasure) {
            $stopMeasure();
        }

        return $result;
    }
}
