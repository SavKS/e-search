<?php

namespace Savks\ESearch\Builder\DSL;

use stdClass;

class MatchNoneCondition extends Condition
{
    public function isEmpty(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return [
            'match_none' => new stdClass(),
        ];
    }
}
