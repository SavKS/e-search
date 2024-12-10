<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;
use Illuminate\Support\LazyCollection;
use Savks\ESearch\Builder\Builder;
use Savks\ESearch\Builder\Result;

/**
 * @mixin Builder
 */
trait HasLazyEachBy
{
    use HasLazyChunkBy;

    /**
     * @return LazyCollection<Result>
     */
    public function lazyEachBy(
        string $field,
        int $limit,
        bool $withMapping = false,
        ?Closure $mapResolver = null
    ): LazyCollection {
        return $this
            ->lazyChunkBy(
                $field,
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
    public function lazyEachByWithMapping(string $field, int $limit, ?Closure $mapResolver = null): LazyCollection
    {
        return $this->lazyEachBy($field, $limit, true, $mapResolver);
    }
}
