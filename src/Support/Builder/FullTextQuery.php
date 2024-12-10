<?php

namespace Savks\ESearch\Support\Builder;

use Closure;
use Savks\ESearch\Builder\DSL\Query;
use Savks\ESearch\Builder\DSL\Queryable;
use stdClass;

class FullTextQuery implements Queryable
{
    protected SearchParams $params;

    public function __construct(
        protected readonly mixed $term,
        protected readonly array $fields
    ) {
        $this->params = new SearchParams();
    }

    public function changeSearchParams(SearchParams|Closure $predicate): static
    {
        $params = $predicate($this->params);

        if ($predicate instanceof Closure) {
            $this->params = $predicate($this->params);
        } elseif ($params instanceof SearchParams) {
            $this->params = $params;
        }

        return $this;
    }

    protected function checkTerm(mixed $term): bool
    {
        return is_string($term) && json_encode($term) !== false;
    }

    public function term(): ?string
    {
        return $this->checkTerm($this->term) ? $this->term : null;
    }

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
            'match_none' => new stdClass(),
        ]);
    }

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

    protected function prepareSearchTerm(): string
    {
        $term = $this->clearTerm();

        $simpleWords = [];

        foreach (explode(' ', $term) as $word) {
            if (empty(trim($word))) {
                continue;
            }

            $simpleWords[] = $word;
        }

        return implode(' ', $simpleWords);
    }

    protected function prepareSearchQuery(): string
    {
        $term = $this->prepareSearchTerm();

        if (! $term) {
            return '';
        }

        $wildcardWords = [];
        $fuzzyWords = [];

        foreach (explode(' ', $term) as $word) {
            if ($this->params->wildcard === true || $this->params->wildcard === 'right') {
                $wildcardWords[] = $word . '*';
            } elseif ($this->params->wildcard === 'left') {
                $wildcardWords[] = '*' . $word;
            } elseif ($this->params->wildcard === 'both') {
                $wildcardWords[] = '*' . $word . '*';
            }

            if ($this->params->fuzzy === true) {
                $fuzzyWords[] = $word . '~';
            } elseif (is_int($this->params->fuzzy)) {
                $fuzzyWords[] = $word . '~' . $this->params->fuzzy;
            }
        }

        if (! $wildcardWords && $fuzzyWords) {
            return $term;
        }

        $query = "(\"{$term}\") OR ({$term})";

        if ($wildcardWords) {
            $query .= ' OR (' . implode(' ', $wildcardWords) . ')';
        }

        if ($fuzzyWords) {
            $query .= ' OR (' . implode(' ', $fuzzyWords) . ')';
        }

        return $query;
    }
}
