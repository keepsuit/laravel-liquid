<?php

namespace Keepsuit\LaravelLiquid\Filters;

use Keepsuit\Liquid\Filters\FiltersProvider;

class DebugFilters extends FiltersProvider
{
    public function dump(mixed $value): mixed
    {
        dump($value);

        return $value;
    }

    public function dd(mixed $value): void
    {
        dd($value);
    }
}
