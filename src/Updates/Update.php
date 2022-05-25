<?php

namespace Savks\ESearch\Updates;

abstract class Update
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var array
     */
    protected array $payload;

    /**
     * @param string $name
     * @param array $payload
     */
    public function __construct(string $name, array $payload)
    {
        $this->name = $name;
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    abstract public static function type(): string;

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
