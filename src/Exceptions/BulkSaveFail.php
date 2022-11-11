<?php

namespace Savks\ESearch\Exceptions;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Illuminate\Support\Collection;

final class BulkSaveFail extends OperationFail
{
    public function __construct(protected readonly array $failedItems)
    {
        $message = \sprintf(
            "Failed to save items into indices \"%s\". Items: %s",
            collect($this->failedItems)->pluck('_index')->unique()->implode(', '),
            \collect($this->failedItems)->groupBy('_index')->map(
                fn (Collection $items, string $index) => \sprintf(
                    '"%s: %s"',
                    $index,
                    $items->pluck('_id')->implode(', ')
                )
            )->values()->implode(',')
        );

        parent::__construct($message);
    }

    public function context(): array
    {
        return $this->failedItems;
    }

    public static function makeFromResponse(ElasticsearchResponse $response): self
    {
        $failedItems = [];

        foreach ($response['items'] as $item) {
            if ($item['index']['status'] >= 400) {
                $failedItems[] = $item['index'];
            }
        }

        return new self($failedItems);
    }
}
