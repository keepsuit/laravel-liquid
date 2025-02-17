<?php

namespace Keepsuit\LaravelLiquid\Filters;

use Keepsuit\Liquid\Contracts\MapsToLiquid;
use Keepsuit\Liquid\Drop;
use Keepsuit\Liquid\Filters\FiltersProvider;

class DebugFilters extends FiltersProvider
{
    public function dump(mixed $value): string
    {
        ob_start();
        dump($this->mapValue($value));
        $dump = ob_get_contents();
        ob_end_clean();

        if ($dump === false) {
            return '';
        }

        return $dump;
    }

    public function dd(mixed $value): never
    {
        dd($this->mapValue($value));
    }

    protected function mapValue(mixed $value): mixed
    {
        if ($value instanceof Drop) {
            return $this->mapValue($value->toArray());
        }

        if ($value instanceof MapsToLiquid) {
            return $this->mapValue($value->toLiquid());
        }

        if (is_iterable($value)) {
            return array_map(fn (mixed $item) => $this->mapValue($item), iterator_to_array($value));
        }

        return $value;
    }
}
