<?php

namespace Savks\ESearch\Exceptions;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Support\Collection;

class BulkSaveFail extends OperationFail
{
    /**
     * @var array
     */
    protected array $failedItems;

    /**
     * @param array $failedItems
     */
    public function __construct(array $failedItems)
    {
        $this->failedItems = $failedItems;

        $message = \sprintf(
            "Failed to save items into indices \"%s\". Items: %s",
            collect($this->failedItems)->pluck('_index')->unique()->implode(', '),
            \collect($this->failedItems)->groupBy('_index')->map(
                fn(Collection $items, string $index) => \sprintf(
                    '"%s: %s"',
                    $index,
                    $items->pluck('_id')->implode(', ')
                )
            )->values()->implode(',')
        );

        parent::__construct($message);
    }

    /**
     * @return array
     */
    public function context(): array
    {
        return $this->failedItems;
    }

    /**
     * @param ElasticsearchResponse $response
     * @return static
     */
    public static function makeFromResponse(ElasticsearchResponse $response): static
    {
        $failedItems = [];

        foreach ($response['items'] as $item) {
            if ($item['index']['status'] >= 400) {
                $failedItems[] = $item['index'];
            }
        }

        return new static($failedItems);
    }
}
