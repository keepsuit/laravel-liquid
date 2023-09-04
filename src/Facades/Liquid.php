<?php

namespace Keepsuit\LaravelLiquid\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Keepsuit\LaravelLiquid\Liquid
 */
class Liquid extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Keepsuit\LaravelLiquid\Liquid::class;
    }
}
