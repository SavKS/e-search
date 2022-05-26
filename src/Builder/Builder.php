<?php

namespace Savks\ESearch\Builder;

use Closure;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use ESearch;
use InvalidArgumentException;
use RuntimeException;
use Savks\ESearch\Support\Resource;

use Elastic\Elasticsearch\Exception\{
    ClientResponseException,
    ServerResponseException
};
use Illuminate\Support\{
    Arr,
    Str
};
use Savks\ESearch\Builder\DSL\{
    Query,
    Queryable
};

class Builder
{
    /**
     * @var Resource
     */
    public readonly Resource $resource;

    /**
     * @var int
     */
    final protected const DEFAULT_LIMIT = 12;

    /**
     * @var int
     */
    protected int $itemsLimit = 10000;

    /**
     * @var array
     */
    protected array $config;

    /**
     * @var array
     */
    protected array $sortConfig = [
        'ids' => [],
        'payload' => null,
    ];

    /**
     * @var bool
     */
    protected bool $isSortWithScore = false;

    /**
     * @var int
     */
    protected int $limit = self::DEFAULT_LIMIT;

    /**
     * @var int
     */
    protected int $offset = 0;

    /**
     * @var array|null
     */
    protected ?array $selectedFields = null;

    /**
     * @var Query[]
     */
    protected array $queries = [];

    /**
     * @var bool
     */
    protected bool $isTrackPerformanceEnabled;

    /**
     * @var array
     */
    protected array $customAggregations = [];

    /**
     * @var bool
     */
    protected bool $skipHits = false;

    /**
     * @param Resource $resource
     */
    public function __construct(Resource $resource)
    {
        $this->resource = $resource;

        $this->limit = static::DEFAULT_LIMIT;

        $this->isTrackPerformanceEnabled = (bool)\config('e-search.enable_track_performance');
    }

    /**
     * @return $this
     */
    public function enablePerformanceTracking(): static
    {
        $this->isTrackPerformanceEnabled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disablePerformanceTracking(): static
    {
        $this->isTrackPerformanceEnabled = true;

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function select(array $fields): self
    {
        $this->selectedFields = $fields;

        return $this;
    }

    /**
     * @param array|string|null $data
     * @param array $options
     * @param array|string|null $fallback
     * @param bool $visibleOnly
     * @return $this
     */
    public function sortByWithScore(
        array|string|null $data,
        array $options = [],
        array|string $fallback = null,
        bool $visibleOnly = true
    ): self {
        $this->isSortWithScore = true;

        return $this->sortBy($data, $options, $fallback, $visibleOnly);
    }

    /**
     * @param array|string|null $data
     * @param array $options
     * @param array|string|null $fallback
     * @param bool $visibleOnly
     * @return $this
     */
    public function sortBy(
        array|string|null $data,
        array $options = [],
        array|string $fallback = null,
        bool $visibleOnly = true
    ): self {
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
     * @param int|string $key
     * @param array|string $value
     * @param bool $visibleOnly
     * @return array
     *
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

    /**
     * @param int $count
     * @return $this
     */
    public function limit(int $count): self
    {
        $this->limit = $count;

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutHits(): self
    {
        $this->skipHits = true;

        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function offset(int $count): self
    {
        $this->offset = $count;

        return $this;
    }

    /**
     * @param string $id
     * @return bool
     *
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
     * @return int
     */
    protected function calcOffset(): int
    {
        return $this->offset + $this->limit > $this->itemsLimit ?
            $this->itemsLimit - $this->limit :
            $this->offset;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setItemsLimit(int $value): Builder
    {
        $this->itemsLimit = $value;

        return $this;
    }

    /**
     * @param int $page
     * @return bool
     */
    protected function isExceededPageLimit(int $page): bool
    {
        return $this->limit * $page > $this->itemsLimit;
    }

    /**
     * @return float
     */
    public function lastAllowedPage(): float
    {
        return \floor($this->itemsLimit / $this->limit);
    }

    /**
     * @param Query|Queryable|callable $predicate
     * @return $this
     */
    public function addQuery(Query|Queryable|callable $predicate): self
    {
        if ($predicate instanceof Query) {
            $query = $predicate;
        } elseif ($predicate instanceof Queryable) {
            $query = $predicate->toQuery();
        } else {
            $query = new Query();

            $predicate($query);
        }

        if (! $query->isEmpty()) {
            $this->queries[] = $query;
        }

        return $this;
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function toDSLQuery(): array
    {
        $dslQuery = [
            'index' => $this->resource->prefixedIndexName(),
            'from' => $this->skipHits ? 0 : $this->calcOffset(),
            'size' => $this->skipHits ? 0 : $this->limit,
            'body' => [
                'query' => $this->toBodyQuery(),
            ],
        ];

        if ($this->customAggregations) {
            $dslQuery['body']['aggs'] = [];

            foreach ($this->customAggregations as $customAggregationName => $customAggregationData) {
                $dslQuery['body']['aggs'][$customAggregationName] = $customAggregationData;
            }
        }

        if ($this->selectedFields) {
            $dslQuery['_source'] = $this->selectedFields;
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

            $dslQuery['body']['sort'] = $this->isSortWithScore ?
                \array_merge($scoreSort, $this->sortConfig['payload']) :
                $this->sortConfig['payload'];
        }

        return $dslQuery;
    }

    /**
     * @param int $flags
     * @return array|string
     */
    public function toJsonDSLQuery(int $flags = 0): array|string
    {
        $dslQuery = $this->toDSLQuery();

        return \json_encode($dslQuery, $flags);
    }

    /**
     * @param bool $pretty
     * @param int $flags
     * @return string
     */
    public function toKibana(bool $pretty = false, int $flags = 0): string
    {
        $result = [
            "POST {$this->resource->prefixedIndexName()}/_search",
        ];

        $query = $this->toDSLQuery();

        $result[] = \json_encode(
            \array_merge(
                [
                    'from' => $query['from'],
                    'size' => $query['size'],
                ],
                $query['body']
            ),
            \JSON_UNESCAPED_UNICODE | ($pretty ? \JSON_PRETTY_PRINT : 0) | $flags
        );

        return \implode("\n", $result);
    }

    /**
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return Result
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
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return Result
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function all(bool $withMapping = false, Closure $mapResolver = null): Result
    {
        $oldLimit = $this->limit;

        $this->limit($this->itemsLimit);

        $result = $this->get($withMapping, $mapResolver);

        $this->limit($oldLimit);

        return $result;
    }

    /**
     * @param int $page
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return Result
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function paginate(int $page, bool $withMapping = false, Closure $mapResolver = null): Result
    {
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

    /**
     * @return ElasticsearchResponse
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function exec(): ElasticsearchResponse
    {
        $queryUniqId = Str::random();

        if ($this->isTrackPerformanceEnabled && \function_exists('clock')) {
            \clock()->event("ESearch: Search in \"{$this->resource::name()}\"", [
                'name' => $queryUniqId,
            ])->begin();
        }

        $rawResponse = ESearch::client()->search(
            $this->toDSLQuery()
        );

        if ($this->isTrackPerformanceEnabled && \function_exists('clock')) {
            \clock()->event("ESearch: Search in \"{$this->resource::name()}\"", [
                'name' => $queryUniqId,
            ])->end();
        }

        return $rawResponse;
    }

    /**
     * @param string $field
     * @param int $limit
     * @param Closure $callback
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return void
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function chunkBy(
        string $field,
        int $limit,
        Closure $callback,
        bool $withMapping = false,
        Closure $mapResolver = null
    ): void {
        $done = false;

        $lastField = $field === '_id' ? $field : "_source.{$field}";
        $lastValue = null;

        while (! $done) {
            $dslQuery = $this->toDSLQuery();

            $dslQuery['size'] = $limit;

            if ($lastValue) {
                $dslQuery['body']['query'] = [
                    'bool' => [
                        'must' => [
                            $dslQuery['body']['query'],

                            [
                                'range' => [
                                    $field => [
                                        'gt' => $lastValue,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            $dslQuery['body']['sort'] = [
                $field => [
                    'order' => 'asc',
                ],
            ];

            $response = ESearch::client()->search($dslQuery);

            $rawResult = $this->normalizeRawResult($response);

            $count = \count($rawResult['hits']['hits']);

            if ($count === 0) {
                break;
            }

            $done = $count < $limit;

            $lastValue = Arr::get(
                last($rawResult['hits']['hits']),
                $lastField
            );

            $resultFactory = new ResultFactory($this->resource, $rawResult, $response);

            if ($withMapping) {
                $resultFactory->withMapping($mapResolver);
            }

            $result = $resultFactory->toResult($limit);

            if ($callback($result) === false) {
                break;
            }
        }
    }

    /**
     * @param string $field
     * @param int $limit
     * @param Closure $callback
     * @param Closure $mapResolver
     * @return void
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function chunkByWithMapping(string $field, int $limit, Closure $callback, Closure $mapResolver): void
    {
        $this->chunkBy($field, $limit, $callback, true, $mapResolver);
    }

    /**
     * @return int
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function count(): int
    {
        $query = new CountQuery($this, $this->isTrackPerformanceEnabled);

        return $query->resolve();
    }

    /**
     * @param ElasticsearchResponse $response
     * @param int|null $page
     * @return array
     */
    protected function normalizeRawResult(ElasticsearchResponse $response, int $page = null): array
    {
        $result = $response->asArray();

        $result['hits']['total']['value'] = \min(
            $result['hits']['total']['value'],
            $this->maxAllowedItems()
        );

        if ($page && $page > $this->lastAllowedPage()) {
            $result['hits']['hits']['value'] = $this->itemsLimit;
        }

        return $result;
    }

    /**
     * @return int
     */
    protected function maxAllowedItems(): int
    {
        return (int)(($this->lastAllowedPage() - 1) * $this->limit + $this->limit);
    }

    /**
     * @param string $name
     * @param array ...$args
     * @return Builder
     */
    public function applyScope(string $name, ...$args): Builder
    {
        $scopeCallback = $this->resource->config()->scopes->findByName($name);

        if (! $scopeCallback) {
            throw new RuntimeException("Scope with name \"{$name}\" not defined");
        }

        return $scopeCallback($this, ...$args) ?? $this;
    }

    /**
     * @param string $name
     * @param array $data
     * @return $this
     */
    public function addCustomAggregation(string $name, array $data): Builder
    {
        $this->customAggregations[$name] = $data;

        return $this;
    }
}
