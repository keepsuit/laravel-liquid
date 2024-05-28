<?php

namespace Keepsuit\LaravelLiquid\Filters;

use Countable;
use Keepsuit\Liquid\Filters\FiltersProvider;

class TranslatorFilters extends FiltersProvider
{
    public function trans(string $input, ?string $locale = null, mixed ...$replace): string
    {
        return trans($input, $replace, $locale);
    }

    public function t(string $input, ?string $locale = null, mixed ...$replace): string
    {
        return $this->trans($input, $locale, ...$replace);
    }

    public function trans_choice(string $input, Countable|int|float|array $count, ?string $locale = null, mixed ...$replace): string
    {
        return trans_choice($input, $count, $replace, $locale);
    }
}
