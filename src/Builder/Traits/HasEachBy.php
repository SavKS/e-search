<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;
use Savks\ESearch\Builder\Result;

/**
 * Trait HasEachBy
 * @package Savks\ESearch\Builder\Traits
 *
 * @mixin Builder
 */
trait HasEachBy
{
    use HasChunkBy;

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
    public function eachBy(
        string $field,
        int $limit,
        Closure $callback,
        bool $withMapping = false,
        Closure $mapResolver = null
    ): void {
        $this->chunkBy($field,
            $limit,
            function (Result $result) use ($callback): ?bool {
                foreach ($result->items as $item) {
                    if ($callback($item) === false) {
                        return false;
                    }
                }

                return null;
            },
            $withMapping,
            $mapResolver
        );
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
    public function eachByWithMapping(string $field, int $limit, Closure $callback, ?Closure $mapResolver = null): void
    {
        $this->eachBy($field, $limit, $callback, true, $mapResolver);
    }
}
