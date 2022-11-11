<?php

namespace Savks\ESearch\Builder;

use Closure;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;
use Savks\ESearch\Elasticsearch\Client;
use Savks\ESearch\Support\Resource;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    ServerResponseException
};
use Savks\ESearch\Builder\DSL\{
    Query,
    Queryable
};

class Builder
{
    use Traits\HasChunkBy;
    use Traits\HasEachBy;
    use Traits\HasLazyChunk;
    use Traits\HasLazyChunkBy;
    use Traits\HasLazyEach;
    use Traits\HasLazyEachBy;

    public readonly Client $client;

    final protected const DEFAULT_LIMIT = 12;

    protected ?int $maxItemsLimit = null;

    protected int $maxItemsLimitSettingValue;

    protected array $config;

    protected array $sortConfig = [
        'ids' => [],
        'payload' => null,
    ];

    protected bool $isSortWithScore = false;

    protected int $limit = self::DEFAULT_LIMIT;

    protected int $offset = 0;

    protected ?array $selectedFields = null;

    /**
     * @var Query[]
     */
    protected array $queries = [];

    protected array $customAggregations = [];

    protected bool $skipHits = false;

    public function __construct(
        public readonly Resource $resource,
        string $connection = null
    ) {
        $this->client = new Client($connection);

        $this->limit = static::DEFAULT_LIMIT;
    }

    public function select(array $fields): static
    {
        $this->selectedFields = $fields;

        return $this;
    }

    public function sortByWithScore(
        array|string|null $data,
        array $options = [],
        array|string $fallback = null,
        bool $visibleOnly = true
    ): static {
        $this->isSortWithScore = true;

        return $this->sortBy($data, $options, $fallback, $visibleOnly);
    }

    public function sortBy(
        array|string|null $data,
        array $options = [],
        array|string $fallback = null,
        bool $visibleOnly = true
    ): static {
        $this->sortConfig['ids'] = [];

        try {
            if (is_string($data)
                && $this->validateSort($data)
            ) {
                $this->sortConfig['payload'] = $this->resource->config()->sorts->findByIdOrFail(
                    $data,
                    $visibleOnly
                )->toArray($options);

                $this->sortConfig['ids'] = [$data];

                return $this;
            }

            if (! is_array($data) || empty($data)) {
                throw new InvalidArgumentException('Invalid sort data type. Must be array or string');
            }

            $sorts = [];

            foreach ($data as $key => $value) {
                $sorts[] = $this->processSort($key, $value, $visibleOnly);
            }

            $this->sortConfig['payload'] = array_merge(...$sorts);
            $this->sortConfig['ids'] = array_values($this->sortConfig['ids']);
        } catch (InvalidArgumentException $e) {
            if ($fallback !== null) {
                return $this->sortBy($fallback, $options, null, $visibleOnly);
            }

            throw $e;
        }

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function processSort(int|string $key, array|string $value, bool $visibleOnly = true): array
    {
        $sorts = [];

        switch (true) {
            case is_int($key) && is_string($value):
                $sorts[] = $this->resource->config()->sorts->findByIdOrFail(
                    $value,
                    $visibleOnly
                )->toArray();

                $this->sortConfig['ids'][] = $value;
                break;

            case is_string($key) && is_array($value):
                $sorts[] = $this->resource->config()->sorts->findByIdOrFail(
                    $key,
                    $visibleOnly
                )->toArray($value);

                $this->sortConfig['ids'][] = $key;
                break;

            case is_int($key) && is_array($value):
                $subSorts = [];

                foreach ($value as $subKey => $subValue) {
                    $subSorts[] = $this->processSort($subKey, $subValue, $visibleOnly);
                }

                $sorts[] = array_merge(
                    ...array_merge(...$subSorts)
                );

                break;

            default:
                throw new InvalidArgumentException('Invalid sort data');
        }

        return $sorts;
    }

    public function limit(int $count): static
    {
        $this->limit = $count;

        return $this;
    }

    public function withoutHits(): static
    {
        $this->skipHits = true;

        return $this;
    }

    public function offset(int $count): static
    {
        $this->offset = $count;

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateSort(string $id): bool
    {
        if (! $this->resource->config()->sorts->findById($id)) {
            throw new InvalidArgumentException("Sort [{$id}] not defined");
        }

        return true;
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function calcOffset(): int
    {
        return $this->offset + $this->limit > $this->resolveMaxItemsLimit() ?
            $this->resolveMaxItemsLimit() - $this->limit :
            $this->offset;
    }

    public function setMaxItemsLimit(int $value): Builder
    {
        $this->maxItemsLimit = $value;

        return $this;
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function isExceededPageLimit(int $page): bool
    {
        return $this->limit * $page > $this->resolveMaxItemsLimit();
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function lastAllowedPage(): float
    {
        return \floor($this->resolveMaxItemsLimit() / $this->limit);
    }

    public function addQuery(Query|Queryable|callable $predicate): static
    {
        if ($predicate instanceof Query) {
            $query = $predicate;
        } elseif ($predicate instanceof Queryable) {
            $query = $predicate->toQuery();
        } else {
            $query = new Query();

            $newQuery = $predicate($query);

            if ($newQuery instanceof Query) {
                $query = $newQuery;
            } elseif ($newQuery instanceof Queryable) {
                $query = $newQuery->toQuery();
            }
        }

        if (! $query->isEmpty()) {
            $this->queries[] = $query;
        }

        return $this;
    }

    public function toBodyQuery(): array
    {
        $result = [];

        foreach ($this->queries as $query) {
            $result[] = $query->toArray();
        }

        return [
            'bool' => [
                'must' => $result,
            ],
        ];
    }

    public function toRequest(): array
    {
        $request = [
            'from' => $this->skipHits ? 0 : $this->calcOffset(),
            'size' => $this->skipHits ? 0 : $this->limit,
            'body' => [
                'query' => $this->toBodyQuery(),
            ],
        ];

        if ($this->customAggregations) {
            $request['body']['aggs'] = [];

            foreach ($this->customAggregations as $customAggregationName => $customAggregationData) {
                $request['body']['aggs'][$customAggregationName] = $customAggregationData;
            }
        }

        if ($this->selectedFields) {
            $request['_source'] = $this->selectedFields;
        }

        if ($this->sortConfig['payload']) {
            if (Arr::isAssoc($this->sortConfig['payload'])) {
                $scoreSort = [
                    '_score' => [
                        'order' => 'desc',
                    ],
                ];
            } else {
                $scoreSort = [
                    [
                        '_score' => [
                            'order' => 'desc',
                        ],
                    ],
                ];
            }

            $request['body']['sort'] = $this->isSortWithScore ?
                \array_merge($scoreSort, $this->sortConfig['payload']) :
                $this->sortConfig['payload'];
        }

        return $request;
    }

    public function toKibana(bool $pretty = false, int $flags = 0): string
    {
        $indexName = $this->client->connection->resolveIndexName(
            $this->resource->indexName()
        );

        $result = ["POST {$indexName}/_search"];

        [
            'from' => $from,
            'size' => $size,
            'body' => $body,
        ] = $this->toRequest();

        $result[] = \json_encode([
            'from' => $from,
            'size' => $size,

            ...$body,
        ], \JSON_UNESCAPED_UNICODE | ($pretty ? \JSON_PRETTY_PRINT : 0) | $flags);

        return \implode("\n", $result);
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function get(bool $withMapping = false, Closure $mapResolver = null): Result
    {
        $response = $this->exec();

        $factory = new ResultFactory(
            $this->resource,
            $this->normalizeRawResult($response),
            $response
        );

        if ($withMapping) {
            $factory->withMapping($mapResolver);
        }

        return $factory->toResult($this->limit);
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function all(bool $withMapping = false, Closure $mapResolver = null): Result
    {
        $oldLimit = $this->limit;

        $this->limit(
            $this->resolveMaxItemsLimit()
        );

        $result = $this->get($withMapping, $mapResolver);

        $this->limit($oldLimit);

        return $result;
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function paginate(
        bool $withMapping = false,
        Closure $mapResolver = null,
        string $pageName = 'page',
        int $page = null
    ): Result {
        if ($page === null) {
            $page = $this->extractPageNumberFromRequest($pageName);
        }

        $normalizedPage = $this->isExceededPageLimit($page) ? $this->lastAllowedPage() : $page;

        $oldOffset = $this->offset;

        $this->offset(
            ($normalizedPage - 1) * $this->limit
        );

        $response = $this->exec();

        $factory = new ResultFactory(
            $this->resource,
            $this->normalizeRawResult($response, $page),
            $response
        );

        $this->offset($oldOffset);

        if ($withMapping) {
            $factory->withMapping($mapResolver);
        }

        return $factory->toResult($this->limit, $page);
    }

    protected function extractPageNumberFromRequest(string $pageName): int
    {
        $reqPage = \request()->get($pageName);

        if (! \is_numeric($reqPage)) {
            return 1;
        }

        return max(
            1,
            (int)$reqPage
        );
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function exec(): ElasticsearchResponse
    {
        return $this->client->search(
            $this->resource,
            $this->toRequest()
        );
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function count(): int
    {
        $response = $this->client->count(
            $this->resource,
            $this->toBodyQuery()
        );

        return $response['count'];
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function normalizeRawResult(ElasticsearchResponse $response, int $page = null): array
    {
        $result = $response->asArray();

        $result['hits']['total']['value'] = \min(
            $result['hits']['total']['value'],
            $this->maxAllowedItems()
        );

        if ($page && $page > $this->lastAllowedPage()) {
            $result['hits']['total']['value'] = $this->resolveMaxItemsLimit();
        }

        return $result;
    }

    protected function maxAllowedItems(): int
    {
        return (int)(($this->lastAllowedPage() - 1) * $this->limit + $this->limit);
    }

    public function applyScope(string $name, ...$args): Builder
    {
        $scopeCallback = $this->resource->config()->scopes->findByName($name);

        if (! $scopeCallback) {
            throw new RuntimeException("Scope with name \"{$name}\" not defined");
        }

        return $scopeCallback($this, ...$args) ?? $this;
    }

    public function addCustomAggregation(string $name, array $data): Builder
    {
        $this->customAggregations[$name] = $data;

        return $this;
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function resolveMaxItemsLimit(): int
    {
        if ($this->maxItemsLimit !== null) {
            return $this->maxItemsLimit;
        }

        if (! isset($this->maxItemsLimitSettingValue)) {
            $settingValue = $this->client->connection->resolveIndexSettings(
                $this->resource->indexName(),
                'index.max_result_window'
            );

            $this->maxItemsLimitSettingValue = $settingValue ?? 10_000;
        }

        return $this->maxItemsLimitSettingValue;
    }
}
