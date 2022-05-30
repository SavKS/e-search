<?php

namespace Savks\ESearch\Manager;

use Closure;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
use Savks\ESearch\Elasticsearch\RequestTypes;
use Savks\ESearch\Exceptions\InvalidConfiguration;
use stdClass;

use Elastic\Elasticsearch\{
    Exception\AuthenticationException,
    Exception\ClientResponseException,
    Exception\MissingParameterException,
    Exception\ServerResponseException,
    Response\Elasticsearch as ElasticsearchResponse,
    Client
};
use Savks\ESearch\Builder\{
    DSL\Query,
    Connection
};
use Savks\ESearch\Support\{
    RequestConfig,
    RequestConfigContract,
    Resource
};

class Manager
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
        $this->connection = $connection ?? $this->createConnection($connection);

        $this->requestConfig = new RequestConfig();
    }

    /**
     * @return Client
     * @throws AuthenticationException
     */
    public function client(): Client
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
        $response = $this->connection->client()->index(
            $this->requestConfig->applyToRequest(RequestTypes::SAVE, [
                'id' => $document[$resource->documentIdBy()],
                'index' => $this->connection->resolveIndexName(
                    $resource->indexName()
                ),
                'body' => $document,
            ])
        );

        $this->connection->errorsHandler()->processResponse(RequestTypes::SAVE, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @param array|Collection $documents
     * @return ElasticsearchResponse
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function bulkSave(Resource $resource, array|Collection $documents): ElasticsearchResponse
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

        $response = $this->connection->client()->bulk(
            $this->requestConfig->applyToRequest(RequestTypes::BULK_SAVE, $params)
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
        return $this->connection->client()->delete(
            $this->requestConfig->applyToRequest(RequestTypes::DELETE, [
                'id' => $id,
                'index' => $this->connection->resolveIndexName(
                    $resource->indexName()
                ),
            ])
        );
    }

    /**
     * @param Resource $resource
     * @param array|Collection $ids
     * @return ElasticsearchResponse
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function bulkDelete(Resource $resource, array|Collection $ids): ElasticsearchResponse
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

        return $this->connection->client()->bulk(
            $this->requestConfig->applyToRequest(RequestTypes::BULK_DELETE, $params)
        );
    }

    /**
     * @param Resource $resource
     * @param Query $query
     * @return ElasticsearchResponse|Promise
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function deleteByQuery(Resource $resource, Query $query): ElasticsearchResponse|Promise
    {
        return $this->connection->client()->deleteByQuery(
            $this->requestConfig->applyToRequest(RequestTypes::DELETE_BY_QUERY, [
                'index' => $this->connection->resolveIndexName(
                    $resource->indexName()
                ),
                'body' => [
                    'query' => $query->toArray(),
                ],
            ])
        );
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
        return $this->connection->client()->deleteByQuery(
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
     * @param string|null $name
     * @return Connection
     */
    protected function createConnection(string $name = null): Connection
    {
        $name = $name ?? \config('e-search.default_connection');

        if (! $name) {
            throw new InvalidConfiguration("Default connection name is not defined");
        }

        $config = \config("e-search.connections.{$name}");

        if (! $config) {
            throw new InvalidConfiguration("Connection with name \"{$name}\" not defined");
        }

        return new Connection($name, $config);
    }
}
