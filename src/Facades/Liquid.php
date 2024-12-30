<?php

namespace Keepsuit\LaravelLiquid\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\Template;

/**
 * @see \Keepsuit\LaravelLiquid\Liquid
 *
 * @method static Template parse(string $view)
 * @method static HtmlString render(string $view, array $data = [])
 * @method static Environment environment()
 */
class Liquid extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Keepsuit\LaravelLiquid\Liquid::class;
    }
}
