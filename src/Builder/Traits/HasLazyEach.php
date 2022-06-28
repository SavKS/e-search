<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;
use Illuminate\Support\LazyCollection;
use Savks\ESearch\Builder\Result;

/**
 * Trait HasLazyEach
 * @package Savks\ESearch\Builder\Traits
 *
 * @mixin Builder
 */
trait HasLazyEach
{
    use HasLazyChunk;

    /**
     * @param int $limit
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return LazyCollection
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws AuthenticationException
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
     * @param int $limit
     * @param Closure|null $mapResolver
     * @return LazyCollection
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function lazyEachWithMapping(int $limit, ?Closure $mapResolver = null): LazyCollection
    {
        return $this->lazyEach($limit, true, $mapResolver);
    }
}
