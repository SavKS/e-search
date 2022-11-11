<?php

namespace Savks\ESearch\Support;

use Closure;
use Savks\ESearch\Elasticsearch\RequestTypes;

class ClosureRequestConfig implements RequestConfigContract
{
    final public function __construct(protected readonly Closure $closure)
    {
    }

    public static function make(Closure $closure): static
    {
        return new static($closure);
    }

    public function applyToRequest(RequestTypes $requestType, array $request): array
    {
        return ($this->closure)($request, $requestType);
    }
}
