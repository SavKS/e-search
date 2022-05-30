<?php

namespace Savks\ESearch\Support;

use Closure;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Arr;
use Savks\ESearch\Updates\Updates;

use Savks\ESearch\Resources\{
    ResourceCommandRunner,
    ResourceRunner
};

abstract class MutableResource extends Resource
{
    /**
     * @return array
     */
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
     * @param mixed $entity
     * @return array
     */
    public function prepareDocuments(mixed $entity): array
    {
        $data = $this->buildDocument($entity);

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

    /**
     * @param array|null $ids
     * @param int $limit
     * @param Closure $callback
     * @param Closure $resolveCount
     * @param array $criteria
     */
    abstract public function prepareSeed(
        ?array $ids,
        int $limit,
        Closure $callback,
        Closure $resolveCount,
        array $criteria = []
    ): void;

    /**
     * @param array|null $ids
     * @param int $limit
     * @param Closure $callback
     * @param Closure $resolveCount
     * @param array $criteria
     */
    abstract public function prepareClean(
        ?array $ids,
        int $limit,
        Closure $callback,
        Closure $resolveCount,
        array $criteria = []
    ): void;

    /**
     * @param mixed $entity
     * @return array
     */
    abstract public function buildDocument(mixed $entity): array;

    /**
     * @return array
     */
    public function index(): array
    {
        return [];
    }

    /**
     * @return array
     */
    abstract public function mapping(): array;

    /**
     * @param Updates $updates
     * @return Updates
     */
    public function updates(Updates $updates): Updates
    {
        return $updates;
    }

    /**
     * @param string|null $connection
     * @return ResourceRunner
     */
    public static function runner(string $connection = null): ResourceRunner
    {
        return new ResourceRunner(
            new static(),
            $connection
        );
    }

    /**
     * @param string|null $connection
     * @return ResourceCommandRunner
     */
    public static function commandRunner(string $connection = null): ResourceCommandRunner
    {
        return new ResourceCommandRunner(
            new static(),
            $connection
        );
    }
}
