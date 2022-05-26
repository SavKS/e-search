<?php

namespace Savks\ESearch\Support;

use Savks\ESearch\Elasticsearch\RequestTypes;

interface RequestConfigContract
{
    /**
     * @param \Savks\ESearch\Elasticsearch\RequestTypes $requestType
     * @param array $request
     * @return array
     */
    public function applyToRequest(RequestTypes $requestType, array $request): array;
}
