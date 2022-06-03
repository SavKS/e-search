<?php

namespace Savks\ESearch\Builder;

use Closure;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Support\Collection;
use LogicException;
use RuntimeException;

use Illuminate\Pagination\{
    LengthAwarePaginator,
    Paginator
};
use Savks\ESearch\Support\{
    Resources\WithMapping,
    Resource
};

class ResultFactory
{
    /**
     * @var Resource
     */
    protected Resource $resource;

    /**
     * @var array
     */
    protected array $raw;

    /**
     * @var bool
     */
    protected bool $withMapping = false;

    /**
     * @var Closure|null
     */
    protected ?Closure $mapResolver = null;

    /**
     * @var ElasticsearchResponse
     */
    protected ElasticsearchResponse $response;

    /**
     * @param Resource $resource
     * @param array $raw
     * @param ElasticsearchResponse $response
     */
    public function __construct(Resource $resource, array $raw, ElasticsearchResponse $response)
    {
        $this->resource = $resource;
        $this->raw = $raw;
        $this->response = $response;
    }

    /**
     * @param Closure|null $mapResolver
     * @return $this
     */
    public function withMapping(Closure $mapResolver = null): static
    {
        $this->withMapping = true;
        $this->mapResolver = $mapResolver;

        return $this;
    }

    /**
     * @param int $limit
     * @param int|null $page
     * @return Result
     */
    public function toResult(int $limit, int $page = null): Result
    {
        $items = $this->prepareItems();

        if ($page) {
            $paginator = $this->makePaginator(
                $items,
                $limit,
                $page,
                $this->raw['hits']['total']['value']
            );
        } else {
            $paginator = null;
        }

        return new Result(
            $this->raw,
            $items,
            $paginator,
            $this->resource,
            $this->response
        );
    }

    /**
     * @return Collection
     */
    protected function prepareItems(): Collection
    {
        $items = [];

        if ($this->withMapping) {
            $isResolverCalled = $this->mapResolver === null;

            if (! $this->resource instanceof WithMapping) {
                throw new RuntimeException(
                    sprintf(
                        'Resource "%s" must implement "%s"',
                        $this->resource->name(),
                        WithMapping::class
                    )
                );
            }

            $items = $this->resource->mapTo(
                $this->raw,
                $this->mapResolver !== null ?
                    function (...$args) use (&$isResolverCalled) {
                        \call_user_func_array($this->mapResolver, $args);

                        $isResolverCalled = true;
                    } :
                    null
            );

            if (! $isResolverCalled) {
                throw new LogicException('Map resolver was passed but not called');
            }
        } else {
            foreach ($this->raw['hits']['hits'] ?? [] as &$hit) {
                $items[] = $hit['_source'];
            }
        }

        return \collect($items);
    }

    /**
     * @param iterable $items
     * @param int $perPage
     * @param int $page
     * @param int $total
     * @return LengthAwarePaginator
     */
    protected function makePaginator(iterable $items, int $perPage, int $page, int $total): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }
}
