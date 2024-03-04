<?php

namespace Savks\ESearch\Support;

use Illuminate\Http\Resources\MissingValue;
use Savks\ESearch\Resources\NativeResourceRunner;

/**
 * @template TEntity
 */
abstract class NativeMutableResource extends Resource
{
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

    public function index(): array
    {
        return [];
    }

    abstract public function mapping(): array;

    public static function runner(string $connection = null): NativeResourceRunner
    {
        return new NativeResourceRunner(
            new static(),
            $connection
        );
    }
}
