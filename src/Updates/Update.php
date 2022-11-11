<?php

namespace Savks\ESearch\Updates;

abstract class Update
{
    public function __construct(
        public readonly string $name,
        public readonly array $payload
    ) {
    }

    abstract public static function type(): string;
}
