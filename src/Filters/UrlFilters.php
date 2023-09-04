<?php

namespace Keepsuit\LaravelLiquid\Filters;

use Keepsuit\Liquid\Filters\FiltersProvider;

class UrlFilters extends FiltersProvider
{
    public function asset(string $path): string
    {
        return asset($path);
    }

    public function route(string $name, string|int ...$parameters): string
    {
        return route($name, $parameters);
    }

    public function url(string $name, string|int ...$parameters): string
    {
        return url($name, $parameters);
    }

    public function secureAsset(string $path): string
    {
        return secure_asset($path);
    }

    public function secureUrl(string $path): string
    {
        return secure_url($path);
    }
}
