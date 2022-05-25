<?php

namespace Savks\ESearch\Updates;

class MappingUpdate extends Update
{
    /**
     * @return string
     */
    public static function type(): string
    {
        return 'mapping';
    }
}
