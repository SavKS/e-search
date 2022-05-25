<?php

namespace Savks\ESearch\Support;

class RequestConfig implements RequestConfigContract
{
    /**
     * @var string|bool|null
     */
    protected string|bool|null $refresh = null;

    /**
     * @return $this
     */
    public function refresh(): self
    {
        $this->refresh = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function waitForRefresh(): self
    {
        $this->refresh = 'wait_for';

        return $this;
    }

    /**
     * @return $this
     */
    public function dontRefresh(): self
    {
        $this->refresh = null;

        return $this;
    }

    /**
     * @param RequestTypes $requestType
     * @param array $request
     * @return array
     */
    public function applyToRequest(RequestTypes $requestType, array $request): array
    {
        if ($this->refresh !== null) {
            $request['refresh'] = $this->refresh;
        }

        return $request;
    }
}
