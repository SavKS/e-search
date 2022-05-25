<?php

namespace Savks\ESearch\Updates;

use RuntimeException;

class Updates
{
    /**
     * @var MappingUpdate[]|SettingsUpdate[]
     */
    protected array $items = [];

    /**
     * @return MappingUpdate[]|SettingsUpdate[]
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @param string $name
     * @param array $payload
     * @return $this
     */
    public function updateMapping(string $name, array $payload): static
    {
        return $this->addUpdate(MappingUpdate::class, $name, $payload);
    }

    /**
     * @param string $name
     * @param array $payload
     * @return $this
     */
    public function updateSettings(string $name, array $payload): static
    {
        return $this->addUpdate(SettingsUpdate::class, $name, $payload);
    }

    /**
     * @param class-string<MappingUpdate|SettingsUpdate> $classFNQ
     * @param string $name
     * @param array $payload
     * @return $this
     */
    protected function addUpdate(string $classFNQ, string $name, array $payload): static
    {
        if (isset($this->items[$name])) {
            throw new RuntimeException("Update with name \"{$name}\" already exists");
        }

        $this->items[$name] = new $classFNQ($name, $payload);

        return $this;
    }
}
