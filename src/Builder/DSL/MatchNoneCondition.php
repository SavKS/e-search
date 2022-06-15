<?php

namespace Savks\ESearch\Builder\DSL;

use stdClass;

class MatchNoneCondition extends Condition
{
    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return false;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'match_none' => new stdClass(),
        ];
    }
}
