<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Foundation\Application;
use Illuminate\View\Factory;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\EnvironmentFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LiquidServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-liquid');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LaravelLiquidFileSystem::class, function (Application $app) {
            return new LaravelLiquidFileSystem(
                files: $app->make('files'),
                viewFinder: $app->make(Factory::class)->getFinder()
            );
        });

        $this->app->singleton('liquid.factory', function (Application $app): EnvironmentFactory {
            return EnvironmentFactory::new()
                ->setFilesystem($app->make(LaravelLiquidFileSystem::class))
                ->setRethrowErrors($app->hasDebugModeEnabled())
                ->addExtension(new LaravelLiquidExtension);
        });

        $this->app->singleton('liquid.environment', function (Application $app): Environment {
            return $app->make('liquid.factory')->build();
        });

        $this->app->singleton('liquid.compiler', function (Application $app) {
            return new LiquidCompiler(
                files: $app['files'],
                cachePath: $app['config']['view.compiled'],
                basePath: $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                shouldCache: (bool) $app['config']->get('view.cache', true),
            );
        });

        $this->app->bind(Liquid::class, function (Application $app) {
            return new Liquid(
                environment: $app->make('liquid.environment')
            );
        });

        $this->app->afterResolving('view', function (Factory $view) {
            $view->addExtension('liquid', 'liquid', function () {
                $liquidEngine = new LiquidEngine(
                    $this->app['liquid.compiler'],
                    $this->app['files']
                );

                $this->app->terminating(static function () use ($liquidEngine) {
                    $liquidEngine->forgetCompiled();
                });

                return $liquidEngine;
            });
        });
    }
}
