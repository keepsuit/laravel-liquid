<?php

namespace Keepsuit\LaravelLiquid\Filters;

use Keepsuit\Liquid\Filters\FiltersProvider;

class DebugFilters extends FiltersProvider
{
    public function dump(mixed $value): string
    {
        ob_start();
        dump($value);
        $dump = ob_get_contents();
        ob_end_clean();

        if ($dump === false) {
            return '';
        }

        return $dump;
    }

    public function dd(mixed $value): never
    {
        dd($value);
    }
}
