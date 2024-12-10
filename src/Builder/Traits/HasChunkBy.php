<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;
use Savks\ESearch\Builder\Builder;
use Savks\ESearch\Builder\Result;

/**
 * @mixin Builder
 */
trait HasChunkBy
{
    use HasLazyChunkBy;

    /**
     * @param Closure(Result $result):(void|false) $callback
     */
    public function chunkBy(
        string $field,
        int $limit,
        Closure $callback,
        bool $withMapping = false,
        ?Closure $mapResolver = null
    ): void {
        foreach ($this->lazyChunkBy($field, $limit, $withMapping, $mapResolver) as $result) {
            if ($callback($result) === false) {
                break;
            }
        }
    }

    public function chunkByWithMapping(string $field, int $limit, Closure $callback, ?Closure $mapResolver = null): void
    {
        $this->chunkBy($field, $limit, $callback, true, $mapResolver);
    }
}
