<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Closure;
use Savks\ESearch\Builder\DSL\BoolCondition;

trait HasBool
{
    use AsConditionHelper;

    /**
     * @template TCallback of Closure(BoolCondition $bool):void|null
     *
     * @param TCallback $callback
     *
     * @return (TCallback is Closure ? $this : BoolCondition)
     */
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
