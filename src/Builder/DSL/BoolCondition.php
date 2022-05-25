<?php

namespace Savks\ESearch\Builder\DSL;

use Savks\ESearch\Exceptions\EmptyQuery;

class BoolCondition extends Condition
{
    /**
     * @var Query[][]
     */
    protected array $conditions = [
        'should' => [],
        'must' => [],
        'must_not' => [],
        'filter' => [],
    ];

    /**
     * @var int|null
     */
    protected ?int $minimumShouldMatch = null;

    /**
     * @var float|null
     */
    protected ?float $boost = null;

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        $value = true;

        foreach ($this->conditions as $conditions) {
            foreach ($conditions as $condition) {
                if (! $condition->isEmpty()) {
                    $value = false;
                }
            }
        }

        return $value;
    }

    /**
     * @param callable|Query|null $predicate
     * @return $this|Query
     */
    public function must(callable|Query $predicate = null): static|Query
    {
        return $this->addCondition('must', $predicate);
    }

    /**
     * @param callable|Query|null $predicate
     * @return $this|Query
     */
    public function mustNot(callable|Query $predicate = null): static|Query
    {
        return $this->addCondition('must_not', $predicate);
    }

    /**
     * @param callable|Query|null $predicate
     * @return $this|Query
     */
    public function should(callable|Query $predicate = null): static|Query
    {
        return $this->addCondition('should', $predicate);
    }

    /**
     * @param callable|Query|null $predicate
     * @return $this|Query
     */
    public function filter(callable|Query $predicate = null): static|Query
    {
        return $this->addCondition('filter', $predicate);
    }

    /**
     * @param string $condition
     * @param callable|Query|null $predicate
     * @return $this|Query
     */
    protected function addCondition(string $condition, callable|Query $predicate = null): static|Query
    {
        if ($predicate === null) {
            $query = new Query();

            $this->conditions[$condition][] = $query;

            return $query;
        }

        if (\is_callable($predicate)) {
            $query = new Query();

            \call_user_func($predicate, $query);
        } else {
            $query = $predicate;
        }

        $this->conditions[$condition][] = $query;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = [];

        foreach (\array_keys($this->conditions) as $conditionVariant) {
            foreach ($this->conditions[$conditionVariant] as $condition) {
                $result[$conditionVariant][] = $condition->toArray();
            }
        }

        if (! $result) {
            throw new EmptyQuery('Bool query is empty');
        }

        if ($this->minimumShouldMatch !== null) {
            $result['minimum_should_match'] = $this->minimumShouldMatch;
        }

        if ($this->boost !== null) {
            $result['boost'] = $this->boost;
        }

        return [
            'bool' => $result,
        ];
    }
}
