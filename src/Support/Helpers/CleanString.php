<?php

namespace Savks\ESearch\Support\Helpers;

use Stringable;

class CleanString implements Stringable
{
    /**
     * @var string
     */
    public readonly string $rawValue;

    /**
     * @var string
     */
    public readonly string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->rawValue = $value;

        $normalizedValue = \mb_strtolower(
            \preg_replace(
                "/[^а-яa-z\d\'\і\є\ї\ ]/ui",
                '',
                $value
            )
        );

        $this->value = \preg_replace('/(\ ){2,}/', ' ', $normalizedValue);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
