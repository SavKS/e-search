<?php

namespace Savks\ESearch\Support\Resources;

use Closure;
use Illuminate\Support\Collection;

/**
 * @template TEntity
 */
interface WithMapping
{
    /**
     * @param array<string, mixed> $result
     *
     * @return TEntity[]|Collection<int, TEntity>
     */
    public function mapTo(array $result, ?Closure $resolver): array|Collection;
}
