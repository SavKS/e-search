<?php

namespace Savks\ESearch\Support\Builder;

class SearchParams
{
    /**
     * @var string|bool
     */
    public string|bool $wildcard = false;

    /**
     * @var string|bool
     */
    public string|bool $fuzzy = false;

    /**
     * @param string|null $type
     * @return $this
     */
    public function wildcard(string $type = null): SearchParams
    {
        if ($type !== null && !\in_array($type, ['right', 'left', 'both'], true)) {
            throw new \InvalidArgumentException('Wildcard option must have values: true, "right", "left", "both"');
        }

        $this->wildcard = $type === null ? true : $type;

        return $this;
    }

    /**
     * @return $this
     */
    public function wildcardRight(): self
    {
        return $this->wildcard('right');
    }

    /**
     * @return $this
     */
    public function wildcardLeft(): self
    {
        return $this->wildcard('left');
    }

    /**
     * @param int|null $value
     * @return $this
     */
    public function fuzzy(int $value = null): self
    {
        $this->fuzzy = $value === null ? true : $value;

        return $this;
    }
}
