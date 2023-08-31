<?php

namespace Keepsuit\Liquid\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Keepsuit\Liquid\Liquid
 */
class Liquid extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Keepsuit\Liquid\Liquid::class;
    }
}
