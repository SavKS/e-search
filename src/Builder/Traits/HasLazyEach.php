<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;
use Illuminate\Support\LazyCollection;

use Savks\ESearch\Builder\{
    Builder,
    Result
};

/**
 * @mixin Builder
 */
trait HasLazyEach
{
    use HasLazyChunk;

    /**
     * @return LazyCollection<Result>
     */
    public function lazyEach(
        int $limit,
        bool $withMapping = false,
        ?Closure $mapResolver = null
    ): LazyCollection {
        return $this
            ->lazyChunk(
                $limit,
                $withMapping,
                $mapResolver
            )
            ->flatMap(
                fn (Result $result) => $result->items
            );
    }

    /**
     * @return LazyCollection<Result>
     */
    public function lazyEachWithMapping(int $limit, ?Closure $mapResolver = null): LazyCollection
    {
        return $this->lazyEach($limit, true, $mapResolver);
    }
}
