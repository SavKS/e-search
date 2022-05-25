<?php

namespace Savks\ESearch\Manager;

use Closure;
use Http\Promise\Promise;
use Savks\ESearch\Builder\DSL\Query;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use stdClass;

use Elastic\Elasticsearch\{
    Exception\ClientResponseException,
    Exception\MissingParameterException,
    Exception\ServerResponseException,
    Response\Elasticsearch as ElasticsearchResponse,
    Client
};
use Savks\ESearch\Support\{
    Resource,
    RequestConfig,
    RequestConfigContract,
    RequestTypes,
    ResourcesRepository
};

class Manager
{
    /**
     * @var Application
     */
    protected Application $app;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var ResourcesRepository
     */
    protected ResourcesRepository $resources;

    /**
     * @var RequestConfigContract
     */
    protected RequestConfigContract $requestConfig;

    /**
     * @param Application $app
     * @param Client $client
     */
    public function __construct(Application $app, Client $client)
    {
        $this->app = $app;
        $this->client = $client;
        $this->requestConfig = new RequestConfig();

        $this->resources = new ResourcesRepository(
            $this->app['config']->get('e-search.resources', [])
        );
    }

    /**
     * @return Client
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * @param Resource $resource
     * @param array $document
     * @return ElasticsearchResponse|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function save(Resource $resource, array $document): ElasticsearchResponse|Promise
    {
        $response = $this->client()->index(
            $this->requestConfig->applyToRequest(RequestTypes::SAVE, [
                'id' => $document[$resource->documentIdBy()],
                'index' => $resource->prefixedIndexName(),
                'body' => $document,
            ])
        );

        $this->app['e-search.errors-handler']->processResponse(RequestTypes::SAVE, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @param array|Collection $documents
     * @return ElasticsearchResponse
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
                    '_index' => $resource->prefixedIndexName(),
                ],
            ];

            $params['body'][] = $document;
        }

        $response = $this->client()->bulk(
            $this->requestConfig->applyToRequest(RequestTypes::BULK_SAVE, $params)
        );

        $this->app['e-search.errors-handler']->processResponse(RequestTypes::BULK_SAVE, $response);

        return $response;
    }

    /**
     * @param Resource $resource
     * @param int|string $id
     * @return ElasticsearchResponse
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function delete(Resource $resource, int|string $id): ElasticsearchResponse
    {
        return $this->client()->delete(
            $this->requestConfig->applyToRequest(RequestTypes::DELETE, [
                'id' => $id,
                'index' => $resource->prefixedIndexName(),
            ])
        );
    }

    /**
     * @param Resource $resource
     * @param array|Collection $ids
     * @return ElasticsearchResponse
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
                    '_index' => $resource->prefixedIndexName(),
                ],
            ];
        }

        return $this->client()->bulk(
            $this->requestConfig->applyToRequest(RequestTypes::BULK_DELETE, $params)
        );
    }

    /**
     * @param Resource $resource
     * @param Query $query
     * @return ElasticsearchResponse|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function deleteByQuery(Resource $resource, Query $query): ElasticsearchResponse|Promise
    {
        return $this->client()->deleteByQuery(
            $this->requestConfig->applyToRequest(RequestTypes::DELETE_BY_QUERY, [
                'index' => $resource->prefixedIndexName(),
                'body' => $query->toArray(),
            ])
        );
    }

    /**
     * @param Resource $resource
     * @return ElasticsearchResponse
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function truncate(Resource $resource): ElasticsearchResponse
    {
        return $this->client()->deleteByQuery(
            $this->requestConfig->applyToRequest(RequestTypes::TRUNCATE, [
                'index' => $resource->prefixedIndexName(),
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
     * @return ResourcesRepository
     */
    public function resources(): ResourcesRepository
    {
        return $this->resources;
    }
}
