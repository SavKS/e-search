<?php

namespace Savks\ESearch\Exceptions;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;

abstract class OperationFail extends BaseException
{
    abstract public static function makeFromResponse(ElasticsearchResponse $response): static;

    abstract public function context(): array;
}
