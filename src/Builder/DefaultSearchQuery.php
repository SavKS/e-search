<?php

namespace Savks\ESearch\Builder;

use Savks\ESearch\Builder\DSL\Query;
use Savks\ESearch\Support\SearchParams;

class DefaultSearchQuery
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
     * @param mixed|string $term
     * @param array $fields
     * @param SearchParams $params
     */
    public function __construct(mixed $term, array $fields, SearchParams $params)
    {
        $this->term = $term;
        $this->fields = $fields;
        $this->params = $params;
    }

    /**
     * @param mixed $term
     * @return bool
     */
    public static function checkTerm(mixed $term): bool
    {
        return \is_string($term) && \json_encode($term) !== false;
    }

    /**
     * @return string|null
     */
    public function term(): ?string
    {
        return static::checkTerm($this->term) ? $this->term : null;
    }

    /**
     * @return Query
     */
    public function toQuery(): Query
    {
        if (static::checkTerm($this->term)) {
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
                "/[^а-яa-z\d\'\і\є\ї\ \.\-\(\)\[\]\<\>\\\\\/]/ui",
                '',
                $this->term
            )
        );

        $term = addcslashes($term, '+-=&|><!(){}[]^"~*?:\/');

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
