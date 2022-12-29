<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;
use Illuminate\Support\LazyCollection;

use Savks\ESearch\Builder\{
    Builder,
    Result,
    ResultFactory
};

/**
 * @mixin Builder
 */
trait HasLazyChunk
{
    /**
     * @return LazyCollection<Result>
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
     * @return LazyCollection<Result>
     */
    public function lazyChunkWithMapping(int $limit, ?Closure $mapResolver = null): LazyCollection
    {
        return $this->lazyChunk($limit, true, $mapResolver);
    }
}
