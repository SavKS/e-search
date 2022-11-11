<?php

namespace Savks\ESearch\Support\Resources;

use Closure;
use Illuminate\Support\Collection;

interface WithMapping
{
    public function mapTo(array $result, ?Closure $resolver): array|Collection;
}
