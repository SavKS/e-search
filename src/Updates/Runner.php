<?php

namespace Savks\ESearch\Updates;

use Closure;
use ESearch;
use Illuminate\Database\Eloquent\Builder;
use Savks\ESearch\Elasticsearch\Connection;
use Savks\ESearch\Exceptions\UpdateFail;
use Savks\ESearch\Models\ESearchUpdate;
use Savks\ESearch\Support\MutableResource;

use Elastic\Elasticsearch\{
    Exception\AuthenticationException,
    Exception\ClientResponseException,
    Exception\MissingParameterException,
    Exception\ServerResponseException,
    Response\Elasticsearch as ElasticsearchResponse,
    Client
};

class Runner
{
    /**
     * @var MutableResource
     */
    protected MutableResource $resource;

    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var Updates
     */
    protected Updates $updates;

    /**
     * @param MutableResource $resource
     * @param Connection $connection
     */
    public function __construct(MutableResource $resource, Connection $connection)
    {
        $this->resource = $resource;
        $this->connection = $connection;

        $this->updates = $this->resource->updates(
            new Updates()
        );
    }

    /**
     * @return bool
     */
    public function hasUpdates(): bool
    {
        return $this->updates->isNotEmpty();
    }

    /**
     * @return bool
     */
    public function hasAppliedUpdates(): bool
    {
        return $this->newQuery()->exists();
    }

    /**
     * @param Closure|null $stepCallback
     * @return int|null
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     * @throws AuthenticationException
     */
    public function apply(Closure $stepCallback = null): ?int
    {
        if (! $this->hasUpdates()) {
            return null;
        }

        $query = $this->newQuery();

        $query->oldest();

        $eSearchUpdates = $query->get();
        $hasAppliedUpdates = $eSearchUpdates->isNotEmpty();
        $appliedUpdatesCount = $eSearchUpdates->count();

        $client = $this->connection->client();
        $indexName = $this->connection->resolveIndexName(
            $this->resource->indexName()
        );

        $index = 0;
        $newCount = 0;

        foreach ($this->updates->all() as $update) {
            if ($hasAppliedUpdates && $index < $appliedUpdatesCount) {
                /** @var ESearchUpdate $eSearchUpdate */
                $eSearchUpdate = $eSearchUpdates->get($index);

                if ($eSearchUpdate->connection_name !== $this->connection->name
                    || $eSearchUpdate->resource !== $this->resource::name()
                    || $eSearchUpdate->type !== $update::type()
                    || $eSearchUpdate->name !== $update->name()
                ) {
                    throw new UpdateFail(
                        \sprintf(
                            'Applied update not match with update from list. Need "%s" given "%s".',
                            \implode(' — ', [
                                $this->connection->name,
                                $this->resource::name(),
                                $update::type(),
                                $update->name(),
                            ]),
                            \implode(' — ', [
                                $eSearchUpdate->connection_name,
                                $eSearchUpdate->resource,
                                $eSearchUpdate->type,
                                $eSearchUpdate->name,
                            ])
                        ),
                        $this->resource, $update
                    );
                } else {
                    $index++;

                    continue;
                }
            }

            $success = $this->runUpdate($indexName, $update, $client);

            if ($success) {
                /** @var ESearchUpdate $eSearchUpdate */
                $eSearchUpdate = ESearchUpdate::create([
                    'connection_name' => $this->connection->name,
                    'resource' => $this->resource::name(),
                    'type' => $update::type(),
                    'name' => $update->name(),
                ]);

                $newCount++;

                if ($stepCallback) {
                    $stepCallback($eSearchUpdate);
                }
            } else {
                throw new UpdateFail('Elasticsearch error', $this->resource, $update);
            }

            $index++;
        }

        return $newCount;
    }

    /**
     * @return void
     */
    public function clean(): void
    {
        $this->newQuery()->delete();
    }

    /**
     * @return Builder
     */
    protected function newQuery(): Builder
    {
        $query = ESearchUpdate::query();

        $query->where(
            'resource',
            '=',
            $this->resource::name()
        );

        $query->where('connection_name', '=', $this->connection->name);

        return $query;
    }

    /**
     * @param string $indexName
     * @param MappingUpdate|SettingsUpdate $update
     * @param Client $client
     * @return bool
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function runUpdate(string $indexName, MappingUpdate|SettingsUpdate $update, Client $client): bool
    {
        if ($update instanceof MappingUpdate) {
            $result = $this->runMappingUpdate($indexName, $update, $client);
        } else {
            $result = $this->runSettingsUpdate($indexName, $update, $client);
        }

        return $result->asArray()['acknowledged'] ?? false;
    }

    /**
     * @param string $indexName
     * @param MappingUpdate $update
     * @param Client $client
     * @return ElasticsearchResponse
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function runMappingUpdate(string $indexName, MappingUpdate $update, Client $client): ElasticsearchResponse
    {
        return $client->indices()->putMapping([
            'index' => $indexName,
            'body' => $update->payload(),
        ]);
    }

    /**
     * @param string $indexName
     * @param SettingsUpdate $update
     * @param Client $client
     * @return ElasticsearchResponse
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function runSettingsUpdate(
        string $indexName,
        SettingsUpdate $update,
        Client $client
    ): ElasticsearchResponse {
        return $client->indices()->putSettings([
            'index' => $indexName,
            'body' => $update->payload(),
        ]);
    }
}
