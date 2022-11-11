<?php

namespace Savks\ESearch\Builder\DSL\Traits;

use Savks\ESearch\Builder\DSL\Condition;

trait AsConditionHelper
{
    abstract protected function addCondition(Condition $condition): static;
}
