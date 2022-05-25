<?php

namespace Savks\ESearch\Facades;

use Illuminate\Support\Facades\Facade;

class ESearch extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'e-search';
    }
}
