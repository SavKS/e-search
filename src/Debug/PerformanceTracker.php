<?php

namespace Savks\ESearch\Debug;

use Closure;
use Savks\ESearch\Support\Resource;

interface PerformanceTracker
{
    public function runMeasure(Resource $resource): Closure;
}
