<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Foundation\Application;
use Illuminate\View\Factory;
use Keepsuit\LaravelLiquid\Filters\DebugFilters;
use Keepsuit\LaravelLiquid\Filters\TranslatorFilters;
use Keepsuit\LaravelLiquid\Filters\UrlFilters;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\LaravelLiquid\Tags\AuthTag;
use Keepsuit\LaravelLiquid\Tags\CsrfTag;
use Keepsuit\LaravelLiquid\Tags\EnvTag;
use Keepsuit\LaravelLiquid\Tags\ErrorTag;
use Keepsuit\LaravelLiquid\Tags\GuestTag;
use Keepsuit\LaravelLiquid\Tags\SessionTag;
use Keepsuit\LaravelLiquid\Tags\ViteTag;
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
                ->registerTag(ViteTag::class)
                ->registerTag(CsrfTag::class)
                ->registerTag(SessionTag::class)
                ->registerTag(ErrorTag::class)
                ->registerTag(EnvTag::class)
                ->registerTag(AuthTag::class)
                ->registerTag(GuestTag::class)
                ->registerFilters(UrlFilters::class)
                ->registerFilters(TranslatorFilters::class)
                ->registerFilters(DebugFilters::class);
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
