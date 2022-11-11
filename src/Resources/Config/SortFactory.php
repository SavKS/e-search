<?php

namespace Savks\ESearch\Resources\Config;

use Illuminate\Contracts\Container\BindingResolutionException;
use Savks\ESearch\Builder\Sort;

class SortFactory
{
    protected array $config = [];

    public function __construct(array|string $field)
    {
        $this->config = [
            'field' => $field,
            'order' => Sort::ASC,
            'options' => [],
        ];
    }

    public function id(string $value, bool $appendOrder = true): static
    {
        $this->config['id'] = $appendOrder ? "{$value}_{$this->config['order']}" : $value;

        return $this;
    }

    public function name(string $value): static
    {
        $this->config['name'] = $value;

        return $this;
    }

    public function asc(): static
    {
        $this->config['order'] = Sort::ASC;

        return $this;
    }

    public function desc(): static
    {
        $this->config['order'] = Sort::DESC;

        return $this;
    }

    public function options(array $options): static
    {
        $this->config['options'] = $options;

        return $this;
    }

    public function hidden(): static
    {
        $this->config['visible'] = false;

        return $this;
    }

    /**
     * @throws BindingResolutionException
     */
    public function compose(): Sort
    {
        $id = $this->config['id'] ?? "{$this->config['field']}_{$this->config['order']}";

        return Sort::fromArray([
            'id' => $id,
            'name' => $this->config['name'] ?? $id,
            'field' => $this->config['field'],
            'order' => $this->config['order'],
            'options' => $this->config['options'],
            'visible' => $this->config['visible'] ?? true,
        ]);
    }
}
