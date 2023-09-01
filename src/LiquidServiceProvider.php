<?php

namespace Keepsuit\Liquid;

use Illuminate\Foundation\Application;
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

    public function packageRegistered(): void
    {
        $this->app->singleton('liquid.compiler', function (Application $app) {
            return new LiquidCompiler(
                files: $app['files'],
                cachePath: $app['config']['view.compiled'],
                basePath: $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                shouldCache: (bool) $app['config']->get('view.cache', true),
                compiledExtension: '',
            );
        });

        $this->app['view']->addExtension('liquid', 'liquid', function () {
            $liquidEngine = new LiquidEngine(
                $this->app['liquid.compiler'],
                $this->app['files']
            );

            $this->app->terminating(static function () use ($liquidEngine) {
                $liquidEngine->forgetCompiled();
            });

            return $liquidEngine;
        });
    }
}
