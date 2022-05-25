<?php

namespace Savks\ESearch\Support;

interface RequestConfigContract
{
    /**
     * @param RequestTypes $requestType
     * @param array $request
     * @return array
     */
    public function applyToRequest(RequestTypes $requestType, array $request): array;
}
