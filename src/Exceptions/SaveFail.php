<?php

namespace Savks\ESearch\Exceptions;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;

final class SaveFail extends OperationFail
{
    public function __construct(protected readonly array $failInfo)
    {
        $message = \sprintf(
            'Failed to save into indices "%s" item with id "%s"',
            $this->failInfo['_index'],
            $this->failInfo['_id']
        );

        parent::__construct($message);
    }

    public function context(): array
    {
        return $this->failInfo;
    }

    public static function makeFromResponse(ElasticsearchResponse $response): static
    {
        return new self($response);
    }
}
