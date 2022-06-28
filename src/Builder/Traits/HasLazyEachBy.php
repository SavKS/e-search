<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;
use Illuminate\Support\LazyCollection;
use Savks\ESearch\Builder\Result;

/**
 * Trait HasLazyEachBy
 * @package Savks\ESearch\Builder\Traits
 *
 * @mixin Builder
 */
trait HasLazyEachBy
{
    use HasLazyChunkBy;

    /**
     * @param string $field
     * @param int $limit
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return LazyCollection
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws AuthenticationException
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
     * @param string $field
     * @param int $limit
     * @param Closure|null $mapResolver
     * @return LazyCollection
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function lazyEachByWithMapping(string $field, int $limit, ?Closure $mapResolver = null): LazyCollection
    {
        return $this->lazyEachBy($field, $limit, true, $mapResolver);
    }
}
