<?php

namespace Savks\ESearch\Exceptions;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;

abstract class OperationFail extends BaseException
{
    /**
     * @param ElasticsearchResponse $response
     * @return static
     */
    abstract public static function makeFromResponse(ElasticsearchResponse $response): static;

    /**
     * @return array
     */
    abstract public function context(): array;
}
