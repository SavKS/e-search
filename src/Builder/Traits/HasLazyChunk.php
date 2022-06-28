<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;
use Illuminate\Support\LazyCollection;

use Savks\ESearch\Builder\{
    Result,
    ResultFactory
};

/**
 * Trait HasLazyChunk
 * @package Savks\ESearch\Builder\Traits
 *
 * @mixin Builder
 */
trait HasLazyChunk
{
    /**
     * @param int $limit
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return LazyCollection<Result>
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws AuthenticationException
     */
    public function lazyChunk(
        int $limit,
        bool $withMapping = false,
        ?Closure $mapResolver = null
    ): LazyCollection {
        return LazyCollection::make(function () use ($limit, $withMapping, $mapResolver) {
            $done = false;
            $searchAfter = null;

            while (! $done) {
                $dslQuery = $this->toRequest();

                $dslQuery['size'] = $limit;

                if ($searchAfter !== null) {
                    $dslQuery['body']['search_after'] = $searchAfter;
                }

                $response = $this->client->search($this->resource, $dslQuery);

                $rawResult = $this->normalizeRawResult($response);

                $resultFactory = new ResultFactory($this->resource, $rawResult, $response);

                if ($withMapping) {
                    $resultFactory->withMapping($mapResolver);
                }

                $result = $resultFactory->toResult($limit);


                $count = \count($result->hits());
                if ($count === 0) {
                    break;
                }

                $searchAfter = \last($result->hits())['sort'];
                $done = $count < $limit;

                yield $result;
            }
        });
    }

    /**
     * @param int $limit
     * @param Closure|null $mapResolver
     * @return LazyCollection<Result>
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function lazyChunkWithMapping(int $limit, ?Closure $mapResolver = null): LazyCollection
    {
        return $this->lazyChunk($limit, true, $mapResolver);
    }
}
