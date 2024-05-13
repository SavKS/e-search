<?php

namespace Savks\ESearch\Support\Builder;

class SearchParams
{
    public string|bool $wildcard = false;

    public string|bool $fuzzy = false;

    public function wildcard(?string $type = null): SearchParams
    {
        if ($type !== null && ! \in_array($type, ['right', 'left', 'both'], true)) {
            throw new \InvalidArgumentException('Wildcard option must have values: true, "right", "left", "both"');
        }

        $this->wildcard = $type ?? true;

        return $this;
    }

    public function wildcardRight(): self
    {
        return $this->wildcard('right');
    }

    public function wildcardLeft(): self
    {
        return $this->wildcard('left');
    }

    public function fuzzy(?int $value = null): self
    {
        $this->fuzzy = $value ?? true;

        return $this;
    }
}
