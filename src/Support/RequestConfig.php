<?php

namespace Savks\ESearch\Support;

use Savks\ESearch\Elasticsearch\RequestTypes;

class RequestConfig implements RequestConfigContract
{
    protected string|bool|null $refresh = null;

    public function refresh(): self
    {
        $this->refresh = true;

        return $this;
    }

    public function waitForRefresh(): self
    {
        $this->refresh = 'wait_for';

        return $this;
    }

    public function dontRefresh(): self
    {
        $this->refresh = null;

        return $this;
    }

    public function applyToRequest(RequestTypes $requestType, array $request): array
    {
        if ($this->refresh !== null) {
            $request['refresh'] = $this->refresh;
        }

        return $request;
    }
}
