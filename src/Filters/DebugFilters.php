<?php

namespace Keepsuit\LaravelLiquid\Filters;

use Keepsuit\Liquid\Filters\FiltersProvider;

class DebugFilters extends FiltersProvider
{
    public function dump(mixed $value): mixed
    {
        ob_start();
        dump($value);
        $dump = ob_get_contents();
        ob_end_clean();

        return $dump;
    }

    public function dd(mixed $value): void
    {
        dd($value);
    }
}
