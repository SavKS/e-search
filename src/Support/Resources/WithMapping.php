<?php

namespace Savks\ESearch\Support\Resources;

use Closure;
use Illuminate\Support\Collection;

interface WithMapping
{
    /**
     * @param array $result
     * @param Closure|null $resolver
     * @return array|Collection
     */
    public function mapTo(array $result, ?Closure $resolver): array|Collection;
}
