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

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function updateMapping(string $name, array $payload): static
    {
        return $this->addUpdate(MappingUpdate::class, $name, $payload);
    }

    public function updateSettings(string $name, array $payload): static
    {
        return $this->addUpdate(SettingsUpdate::class, $name, $payload);
    }

    protected function addUpdate(string $classFNQ, string $name, array $payload): static
    {
        if (isset($this->items[$name])) {
            throw new RuntimeException("Update with name \"{$name}\" already exists");
        }

        $this->items[$name] = new $classFNQ($name, $payload);

        return $this;
    }
}
