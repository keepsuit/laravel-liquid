<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Foundation\Application;
use Illuminate\View\Factory;
use Keepsuit\LaravelLiquid\Filters\UrlFilters;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\LaravelLiquid\Tags\ViteTag;
use Keepsuit\Liquid\TemplateFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LiquidServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-liquid')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LaravelLiquidFileSystem::class, function (Application $app) {
            return new LaravelLiquidFileSystem(
                files: $app->make('files'),
                viewFinder: $app->make(Factory::class)->getFinder()
            );
        });

        $this->app->singleton(TemplateFactory::class, function (Application $app) {
            return TemplateFactory::new()
                ->setFilesystem($app->make(LaravelLiquidFileSystem::class))
                ->lineNumbers((bool) config('app.debug', false))
                ->registerTag(ViteTag::class)
                ->registerFilter(UrlFilters::class);
        });

        $this->app->singleton('liquid.compiler', function (Application $app) {
            return new LiquidCompiler(
                files: $app['files'],
                cachePath: $app['config']['view.compiled'],
                basePath: $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                shouldCache: (bool) $app['config']->get('view.cache', true),
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
