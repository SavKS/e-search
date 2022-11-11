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
    protected bool $withMapping = false;

    protected ?Closure $mapResolver = null;

    public function __construct(
        protected readonly Resource $resource,
        protected readonly array $raw,
        protected readonly ElasticsearchResponse $response
    ) {
    }

    public function withMapping(Closure $mapResolver = null): static
    {
        $this->withMapping = true;
        $this->mapResolver = $mapResolver;

        return $this;
    }

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

        if (! ($items instanceof Collection)) {
            $items = \collect($items);
        }

        return $items;
    }

    protected function makePaginator(iterable $items, int $perPage, int $page, int $total): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }
}
