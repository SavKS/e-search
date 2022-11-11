<?php

namespace Savks\ESearch\Builder\Traits;

use Closure;

use Savks\ESearch\Builder\{
    Builder,
    Result
};

/**
 * @mixin Builder
 */
trait HasEachBy
{
    use HasChunkBy;

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

    public function eachByWithMapping(string $field, int $limit, Closure $callback, ?Closure $mapResolver = null): void
    {
        $this->eachBy($field, $limit, $callback, true, $mapResolver);
    }
}
