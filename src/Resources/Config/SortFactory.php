<?php

namespace Savks\ESearch\Resources\Config;

use Illuminate\Contracts\Container\BindingResolutionException;
use Savks\ESearch\Builder\Sort;

class SortFactory
{
    /**
     * @var array
     */
    protected array $config = [];

    /**
     * @param array|string $field
     */
    public function __construct(array|string $field)
    {
        $this->config = [
            'field' => $field,
            'order' => Sort::ASC,
            'options' => [],
        ];
    }

    /**
     * @param string $value
     * @param bool $appendOrder
     * @return static
     */
    public function id(string $value, bool $appendOrder = true): static
    {
        $this->config['id'] = $appendOrder ? "{$value}_{$this->config['order']}" : $value;

        return $this;
    }

    /**
     * @param string $value
     * @return static
     */
    public function name(string $value): static
    {
        $this->config['name'] = $value;

        return $this;
    }

    /**
     * @return static
     */
    public function asc(): static
    {
        $this->config['order'] = Sort::ASC;

        return $this;
    }

    /**
     * @return static
     */
    public function desc(): static
    {
        $this->config['order'] = Sort::DESC;

        return $this;
    }

    /**
     * @param array $options
     * @return static
     */
    public function options(array $options): static
    {
        $this->config['options'] = $options;

        return $this;
    }

    /**
     * @return static
     */
    public function hidden(): static
    {
        $this->config['visible'] = false;

        return $this;
    }

    /**
     * @return Sort
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
