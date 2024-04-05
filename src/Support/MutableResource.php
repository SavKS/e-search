<?php

namespace Savks\ESearch\Support;

use Closure;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Arr;
use Savks\ESearch\Resources\ResourceRunner;

/**
 * @template TEntity
 */
abstract class MutableResource extends Resource
{
    public function seedLimit(): int
    {
        return 100;
    }

    public function prepareMapping(): array
    {
        $mapping = $this->mapping();
        $properties = [];

        foreach ($mapping['properties'] ?? [] as $key => $value) {
            if (! $value instanceof MissingValue) {
                $properties[$key] = $value;
            }
        }

        $mapping['properties'] = $properties;

        return $mapping;
    }

    /**
     * @param TEntity $entity
     */
    public function prepareDocuments(mixed $entity): ?array
    {
        $data = $this->buildDocument($entity);

        if ($data === null) {
            return null;
        }

        if (Arr::isList($data)) {
            $result = [];

            foreach ($data as $item) {
                $document = [];

                foreach ($item as $key => $value) {
                    if ($value instanceof MissingValue) {
                        continue;
                    }

                    $document[$key] = $value;
                }

                $result[] = $document;
            }

            return $result;
        }

        $document = [];

        foreach ($data as $key => $value) {
            if ($value instanceof MissingValue) {
                continue;
            }

            $document[$key] = $value;
        }

        return [$document];
    }

    abstract public function prepareSeed(
        ?array $ids,
        int $limit,
        Closure $callback,
        Closure $resolveCount,
        array $criteria = []
    ): void;

    /**
     * @param TEntity $entity
     */
    abstract public function buildDocument(mixed $entity): ?array;

    public function index(): array
    {
        return [];
    }

    abstract public function mapping(): array;

    public static function runner(string $connection = null): ResourceRunner
    {
        return new ResourceRunner(
            new static(),
            $connection
        );
    }
}
