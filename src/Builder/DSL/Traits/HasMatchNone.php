<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\MatchNoneCondition;

trait HasMatchNone
{
    use AsConditionHelper;

    public function matchNone(): static
    {
        return $this->addCondition(
            new MatchNoneCondition()
        );
    }
}
