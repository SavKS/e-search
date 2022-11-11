<?php

namespace Savks\ESearch\Support;

use Savks\ESearch\Elasticsearch\RequestTypes;

interface RequestConfigContract
{
    public function applyToRequest(RequestTypes $requestType, array $request): array;
}
