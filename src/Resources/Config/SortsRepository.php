<?php

namespace Savks\ESearch\Resources\Config;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use RuntimeException;
use Savks\ESearch\Builder\Sort;

class SortsRepository
{
    /**
     * @var Sort[]
     */
    protected array $sorts = [];

    public function add(Sort $sort): static
    {
        $this->sorts[] = $sort;

        return $this;
    }

    /**
     * @return array<int, Sort>
     */
    public function all(bool $visibleOnly = false): array
    {
        if (! $visibleOnly) {
            return $this->sorts;
        }

        $visibleSorts = [];

        foreach ($this->sorts as $sort) {
            if ($sort->visible) {
                $visibleSorts[] = $sort;
            }
        }

        return $visibleSorts;
    }

    /**
     * @throws BindingResolutionException
     */
    public function create(array|string $field, Closure $handler = null): static
    {
        $factory = new SortFactory($field);

        if ($handler) {
            $handler($factory);
        }

        $this->sorts[] = $factory->compose();

        return $this;
    }

    /**
     * @throws BindingResolutionException
     */
    public function createDesc(array|string $field, Closure $handler = null): static
    {
        return $this->create($field, function (SortFactory $factory) use ($handler) {
            $factory->desc();

            if ($handler) {
                $handler($factory);
            }
        });
    }

    /**
     * @throws BindingResolutionException
     */
    public function createBothOrders(array|string $field, Closure $handler = null): static
    {
        $factoryAsc = new SortFactory($field);
        $factoryDesc = new SortFactory($field);

        $factoryDesc->desc();

        if ($handler) {
            $handler($factoryAsc);
            $handler($factoryDesc);
        }

        $this->sorts[] = $factoryAsc->compose();
        $this->sorts[] = $factoryDesc->compose();

        return $this;
    }

    public function findById(string $id, bool $visibleOnly = false): ?Sort
    {
        foreach ($this->all() as $item) {
            if ($item->id === $id) {
                return $visibleOnly && ! $item->visible ? null : $item;
            }
        }

        return null;
    }

    public function findByIdOrFail(string $id, bool $visibleOnly = false): ?Sort
    {
        if ($sort = $this->findById($id, $visibleOnly)) {
            return $sort;
        }

        $sortIds = [];

        foreach ($this->all($visibleOnly) as $sort) {
            $sortIds[] = $sort->id;
        }

        throw new RuntimeException(
            sprintf(
                "Sort with id [%s] not found. Available: %s.",
                $id,
                implode(', ', $sortIds)
            )
        );
    }
}
