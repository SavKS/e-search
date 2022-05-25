<?php

namespace Savks\ESearch\Updates;

class SettingsUpdate extends Update
{
    /**
     * @return string
     */
    public static function type(): string
    {
        return 'settings';
    }
}
