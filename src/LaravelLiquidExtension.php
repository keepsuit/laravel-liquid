<?php

namespace Keepsuit\LaravelLiquid;

use Keepsuit\Liquid\Extensions\Extension;

class LaravelLiquidExtension extends Extension
{
    public function getTags(): array
    {
        return [
            Tags\ViteTag::class,
            Tags\CsrfTag::class,
            Tags\SessionTag::class,
            Tags\ErrorTag::class,
            Tags\EnvTag::class,
            Tags\AuthTag::class,
            Tags\GuestTag::class,
        ];
    }

    public function getFiltersProviders(): array
    {
        return [
            Filters\UrlFilters::class,
            Filters\TranslatorFilters::class,
            Filters\DebugFilters::class,
        ];
    }
}
