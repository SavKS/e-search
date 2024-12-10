<?php

namespace Savks\ESearch\Builder\Traits;

use Arr;
use Closure;
use Illuminate\Support\LazyCollection;
use Savks\ESearch\Builder\Builder;
use Savks\ESearch\Builder\Result;
use Savks\ESearch\Builder\ResultFactory;
use Savks\ESearch\Exceptions\ChunkFieldAbsentException;

/**
 * @mixin Builder
 */
trait HasLazyChunkBy
{
    protected function forPageAgainstField(
        string $field,
        string|int|float|null $value,
        int $limit,
        bool $withMapping = false,
        ?Closure $mapResolver = null
    ): Result {
        $dslQuery = $this->toRequest();

        if (! empty($dslQuery['_source'])) {
            if (! in_array($field, $dslQuery['_source'], true)) {
                $dslQuery['_source'][] = $field;
            }
        }

        $dslQuery['size'] = $limit;

        if ($value !== null) {
            $dslQuery['body']['query'] = [
                'bool' => [
                    'must' => [
                        $dslQuery['body']['query'],

                        [
                            'range' => [
                                $field => [
                                    'gt' => $value,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $dslQuery['body']['sort'] = [
            $field => [
                'order' => 'asc',
            ],
        ];

        $response = $this->client->search($this->resource, $dslQuery);

        $rawResult = $this->normalizeRawResult($response);

        $resultFactory = new ResultFactory($this->resource, $rawResult, $response);

        if ($withMapping) {
            $resultFactory->withMapping($mapResolver);
        }

        return $resultFactory->toResult($limit);
    }

    /**
     * @return LazyCollection<Result>
     */
    public function lazyChunkBy(
        string $field,
        int $limit,
        bool $withMapping = false,
        ?Closure $mapResolver = null
    ): LazyCollection {
        return LazyCollection::make(function () use ($field, $limit, $withMapping, $mapResolver) {
            $done = false;
            $lastValue = null;

            while (! $done) {
                $result = $this->forPageAgainstField(
                    $field,
                    $lastValue,
                    $limit,
                    $withMapping,
                    $mapResolver
                );

                $count = count($result->hits());

                if ($count === 0) {
                    break;
                }

                $done = $count < $limit;

                $lastValue = Arr::get(
                    last($result->hits()),
                    $field === '_id' ? $field : "_source.{$field}"
                );

                if ($lastValue === null) {
                    throw new ChunkFieldAbsentException(
                        sprintf(
                            'Field "%s" is absent in the response, but is was expected to be present',
                            $field
                        )
                    );
                }

                yield $result;
            }
        });
    }

    /**
     * @return LazyCollection<Result>
     */
    public function lazyChunkByWithMapping(string $field, int $limit, ?Closure $mapResolver = null): LazyCollection
    {
        return $this->lazyChunkBy($field, $limit, true, $mapResolver);
    }
}
