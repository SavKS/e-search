<?php

namespace Savks\ESearch\Exceptions;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;

class SaveFail extends OperationFail
{
    /**
     * @var array
     */
    protected array $failInfo;

    /**
     * @param array $failInfo
     */
    public function __construct(array $failInfo)
    {
        $this->failInfo = $failInfo;

        $message = \sprintf(
            'Failed to save into indices "%s" item with id "%s"',
            $this->failInfo['_index'],
            $this->failInfo['_id']
        );

        parent::__construct($message);
    }

    /**
     * @return array
     */
    public function context(): array
    {
        return $this->failInfo;
    }

    /**
     * @param ElasticsearchResponse $response
     * @return static
     */
    public static function makeFromResponse(ElasticsearchResponse $response): static
    {
        return new static($response);
    }
}
