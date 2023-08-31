<?php

namespace Keepsuit\Liquid;

use Keepsuit\Liquid\Commands\LiquidCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LiquidServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-liquid')
            ->hasConfigFile()
            ->hasCommand(LiquidCommand::class);
    }
}
