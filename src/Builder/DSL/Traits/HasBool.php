<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Closure;
use Savks\ESearch\Builder\DSL\BoolCondition;

trait HasBool
{
    use AsConditionHelper;

    public function bool(?Closure $callback = null): static|BoolCondition
    {
        $condition = new BoolCondition();

        $this->addCondition($condition);

        if ($callback) {
            $callback($condition);

            return $this;
        }

        return $condition;
    }
}
