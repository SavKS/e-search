<?php

namespace Savks\ESearch\Builder\DSL;

use Closure;
use Savks\ESearch\Exceptions\EmptyQuery;

class BoolCondition extends Condition
{
    /**
     * @var array{
     *     should: Queryable[],
     *     must: Queryable[],
     *     must_not: Queryable[],
     *     filter: Queryable[],
     * }
     */
    protected array $conditions = [
        'should' => [],
        'must' => [],
        'must_not' => [],
        'filter' => [],
    ];

    protected ?int $minimumShouldMatch = null;

    protected ?float $boost = null;

    public function setMinimumShouldMatch(?int $minimumShouldMatch): static
    {
        $this->minimumShouldMatch = $minimumShouldMatch;

        return $this;
    }

    public function isEmpty(): bool
    {
        $value = true;

        foreach ($this->conditions as $conditions) {
            foreach ($conditions as $condition) {
                if (! $condition->toQuery()->isEmpty()) {
                    $value = false;
                }
            }
        }

        return $value;
    }

    /**
     * @template TPredicate of Closure|Queryable|null
     *
     * @param TPredicate $predicate
     *
     * @return (TPredicate is null ? Query : $this)
     */
    public function must(Closure|Queryable|null $predicate = null): static|Query
    {
        return $this->addCondition('must', $predicate);
    }

    /**
     * @template TPredicate of Closure|Queryable|null
     *
     * @param TPredicate $predicate
     *
     * @return (TPredicate is null ? Query : $this)
     */
    public function mustNot(Closure|Queryable|null $predicate = null): static|Query
    {
        return $this->addCondition('must_not', $predicate);
    }

    /**
     * @template TPredicate of Closure|Queryable|null
     *
     * @param TPredicate $predicate
     *
     * @return (TPredicate is null ? Query : $this)
     */
    public function should(Closure|Queryable|null $predicate = null): static|Query
    {
        return $this->addCondition('should', $predicate);
    }

    /**
     * @template TPredicate of Closure|Queryable|null
     *
     * @param TPredicate $predicate
     *
     * @return (TPredicate is null ? Query : $this)
     */
    public function filter(Closure|Queryable|null $predicate = null): static|Query
    {
        return $this->addCondition('filter', $predicate);
    }

    /**
     * @template TPredicate of Closure|Queryable|null
     *
     * @param 'should'|'must'|'must_not'|'filter' $condition
     * @param TPredicate $predicate
     *
     * @return (TPredicate is null ? Query : $this)
     */
    protected function addCondition(string $condition, Closure|Queryable|null $predicate = null): static|Query
    {
        if ($predicate === null) {
            $query = new Query();

            $this->conditions[$condition][] = $query;

            return $query;
        }

        if (is_callable($predicate)) {
            $query = new Query();

            $predicate($query);
        } else {
            $query = $predicate;
        }

        $this->conditions[$condition][] = $query;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach (array_keys($this->conditions) as $conditionVariant) {
            foreach ($this->conditions[$conditionVariant] as $condition) {
                $result[$conditionVariant][] = $condition->toQuery()->toArray();
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
