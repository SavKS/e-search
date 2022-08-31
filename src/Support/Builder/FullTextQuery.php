<?php

namespace Savks\ESearch\Support\Builder;

use Closure;

use Savks\ESearch\Builder\DSL\{
    Query,
    Queryable
};

class FullTextQuery implements Queryable
{
    /**
     * @var mixed|string
     */
    protected mixed $term;

    /**
     * @var array
     */
    protected array $fields;

    /**
     * @var SearchParams
     */
    protected SearchParams $params;

    /**
     * @param mixed $term
     * @param array $fields
     */
    public function __construct(mixed $term, array $fields)
    {
        $this->term = $term;
        $this->fields = $fields;

        $this->params = new SearchParams();
    }

    /**
     * @param SearchParams|Closure $predicate
     * @return $this
     */
    public function changeSearchParams(SearchParams|Closure $predicate): static
    {
        $params = \call_user_func($predicate, $this->params);

        if ($predicate instanceof Closure) {
            $this->params = \call_user_func($predicate, $this->params);
        } elseif ($params instanceof SearchParams) {
            $this->params = $params;
        }

        return $this;
    }

    /**
     * @param mixed $term
     * @return bool
     */
    protected function checkTerm(mixed $term): bool
    {
        return \is_string($term) && \json_encode($term) !== false;
    }

    /**
     * @return string|null
     */
    public function term(): ?string
    {
        return $this->checkTerm($this->term) ? $this->term : null;
    }

    /**
     * @return Query
     */
    public function toQuery(): Query
    {
        if ($this->checkTerm($this->term)) {
            return (new Query())->raw([
                'query_string' => [
                    'fields' => $this->fields,
                    'query' => $this->prepareSearchQuery(),
                    'default_operator' => 'AND',
                ],
            ]);
        }

        return (new Query())->raw([
            'match_none' => new \stdClass(),
        ]);
    }

    /**
     * @return string
     */
    protected function clearTerm(): string
    {
        $term = mb_strtolower(
            preg_replace(
                "/[^а-яa-z\d\'\і\є\ї\ \.\-_\(\)\[\]\<\>\\\\\/\@]/ui",
                '',
                $this->term
            )
        );

        $term = addcslashes($term, '+-=&|><!(){}[]^"~*?:\/@');

        return preg_replace(
            '/(\ ){2,}/',
            ' ',
            $term
        );
    }

    /**
     * @return string
     */
    protected function prepareSearchTerm(): string
    {
        $term = $this->clearTerm();

        $simpleWords = [];

        foreach (\explode(' ', $term) as $word) {
            if (empty(\trim($word))) {
                continue;
            }

            $simpleWords[] = $word;
        }

        return \implode(' ', $simpleWords);
    }

    /**
     * @return string
     */
    protected function prepareSearchQuery(): string
    {
        $term = $this->prepareSearchTerm();

        if (! $term) {
            return '';
        }

        $wildcardWords = [];
        $fuzzyWords = [];

        foreach (\explode(' ', $term) as $word) {
            if ($this->params->wildcard === true || $this->params->wildcard === 'right') {
                $wildcardWords[] = $word . '*';
            } elseif ($this->params->wildcard === 'left') {
                $wildcardWords[] = '*' . $word;
            } elseif ($this->params->wildcard === 'both') {
                $wildcardWords[] = '*' . $word . '*';
            }

            if ($this->params->fuzzy === true) {
                $fuzzyWords[] = $word . '~';
            } elseif (\is_int($this->params->fuzzy)) {
                $fuzzyWords[] = $word . '~' . $this->params->fuzzy;
            }
        }

        if (! $wildcardWords && $fuzzyWords) {
            return $term;
        }

        $query = "(\"{$term}\") OR ({$term})";

        if ($wildcardWords) {
            $query .= ' OR (' . \implode(' ', $wildcardWords) . ')';
        }

        if ($fuzzyWords) {
            $query .= ' OR (' . \implode(' ', $fuzzyWords) . ')';
        }

        return $query;
    }
}
