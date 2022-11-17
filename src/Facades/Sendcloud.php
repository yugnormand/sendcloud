<?php

namespace Todocoding\Sendcloud\Facades;

use Illuminate\Support\Facades\Facade;

class Sendcloud extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sendcloud';
    }
}
