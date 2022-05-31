<?php

namespace Savks\ESearch\Debug;

use Closure;
use Savks\ESearch\Support\Resource;

interface PerformanceTracker
{
    /**
     * @param Resource $resource
     * @return Closure
     */
    public function runMeasure(Resource $resource): Closure;
}
