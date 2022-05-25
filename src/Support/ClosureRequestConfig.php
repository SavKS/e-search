<?php

namespace Savks\ESearch\Support;

use Closure;

class ClosureRequestConfig implements RequestConfigContract
{
    /**
     * @var Closure
     */
    protected Closure $closure;

    /**
     * @return static
     */
    public static function make(Closure $closure): self
    {
        return new static($closure);
    }

    /**
     * @param Closure $closure
     * @return void
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * @param string $requestType
     * @param array $request
     * @return array
     */
    public function applyToRequest(string $requestType, array $request): array
    {
        return ($this->closure)($request, $requestType);
    }
}
