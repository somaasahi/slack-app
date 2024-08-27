<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Slack extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'slack';
    }
}
