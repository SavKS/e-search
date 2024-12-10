<?php

namespace Savks\ESearch\Debug;

use Closure;
use Savks\ESearch\Support\Resource;
use Str;

class ClockworkPerformanceTracker implements PerformanceTracker
{
    public function runMeasure(Resource $resource): Closure
    {
        $queryUniqId = Str::random();
        $event = "Elasticsearch: Resource \"{$resource::name()}\"";

        if (function_exists('clock')) {
            clock()->event($event, ['name' => $queryUniqId])->begin();
        }

        return function () use ($event, $queryUniqId) {
            if (function_exists('clock')) {
                clock()->event($event, ['name' => $queryUniqId])->end();
            }
        };
    }
}
