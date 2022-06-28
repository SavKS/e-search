<?php

namespace Savks\ESearch\Builder\Traits;

use Arr;
use Closure;
use Illuminate\Support\LazyCollection;
use Savks\ESearch\Exceptions\ChunkFieldAbsentException;

use Savks\ESearch\Builder\{
    Builder,
    Result,
    ResultFactory
};

/**
 * Trait HasLazyChunkBy
 * @package Savks\ESearch\Builder\Traits
 *
 * @mixin Builder
 */
trait HasLazyChunkBy
{
    /**
     * @param string $field
     * @param string $value
     * @param int $limit
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return Result
     */
    protected function forPageAgainstField(
        string $field,
        ?string $value,
        int $limit,
        bool $withMapping = false,
        ?Closure $mapResolver = null
    ): Result {
        $dslQuery = $this->toRequest();

        $dslQuery['size'] = $limit;

        $lastField = $field === '_id' ? $field : "_source.{$field}";

        if ($value !== null) {
            $dslQuery['body']['query'] = [
                'bool' => [
                    'must' => [
                        $dslQuery['body']['query'],

                        [
                            'range' => [
                                $lastField => [
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
     * @param string $field
     * @param int $limit
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return LazyCollection<Result>
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws AuthenticationException
     */
    public function lazyChunkBy(
        string $field,
        int $limit,
        bool $withMapping = false,
        ?Closure $mapResolver = null
    ): LazyCollection {
        return LazyCollection::make(function () use ($field, $limit, $withMapping, $mapResolver) {
            $done = false;

            $lastField = $field === '_id' ? $field : "_source.{$field}";
            $lastValue = null;

            while (! $done) {
                $result = $this->forPageAgainstField(
                    $field,
                    $lastValue,
                    $limit,
                    $withMapping,
                    $mapResolver
                );

                $count = \count($result->hits());

                if ($count === 0) {
                    break;
                }

                $done = $count < $limit;

                $lastValue = Arr::get(
                    \last($result->hits()),
                    $lastField
                );

                if ($lastValue === null) {
                    throw new ChunkFieldAbsentException(
                        \sprintf(
                            'Field "%s" is absent in the response, but is was expected to be present',
                            $lastField
                        )
                    );
                }

                yield $result;
            }
        });
    }

    /**
     * @param string $field
     * @param int $limit
     * @param Closure|null $mapResolver
     * @return LazyCollection<Result>
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function lazyChunkByWithMapping(string $field, int $limit, ?Closure $mapResolver = null): LazyCollection
    {
        return $this->lazyChunkBy($field, $limit, true, $mapResolver);
    }
}
