<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;

/**
 * Trait HasChunkBy
 * @package Savks\ESearch\Builder\Traits
 *
 * @mixin Builder
 */
trait HasChunkBy
{
    use HasLazyChunkBy;

    /**
     * @param string $field
     * @param int $limit
     * @param Closure $callback
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return void
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws AuthenticationException
     */
    public function chunkBy(
        string $field,
        int $limit,
        Closure $callback,
        bool $withMapping = false,
        Closure $mapResolver = null
    ): void {
        foreach ($this->lazyChunkBy($field, $limit, $withMapping, $mapResolver) as $result) {
            if ($callback($result) === false) {
                break;
            }
        }
    }

    /**
     * @param string $field
     * @param int $limit
     * @param Closure $callback
     * @param Closure|null $mapResolver
     * @return void
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function chunkByWithMapping(string $field, int $limit, Closure $callback, ?Closure $mapResolver = null): void
    {
        $this->chunkBy($field, $limit, $callback, true, $mapResolver);
    }
}
