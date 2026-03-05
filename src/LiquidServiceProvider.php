<?php

namespace Keepsuit\LaravelLiquid;

use Clockwork\Clockwork;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\View\Factory;
use Keepsuit\LaravelLiquid\Support\Clockwork\LiquidDataSource;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\LaravelLiquid\Support\LaravelTemplatesCache;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\EnvironmentFactory;
use Keepsuit\Liquid\Extensions\Extension;
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
        $this->app->singleton('liquid.factory', function (Application $app): EnvironmentFactory {
            $filesystem = new LaravelLiquidFileSystem(
                compiler: $app->make('liquid.compiler')
            );

            $templatesCache = new LaravelTemplatesCache(
                compiler: $app->make('liquid.compiler')
            );

            $environment = EnvironmentFactory::new()
                ->setFilesystem($filesystem)
                ->setTemplatesCache($templatesCache)
                ->setRethrowErrors($app->hasDebugModeEnabled());

            Collection::make(config()->array('liquid.extensions', [LaravelLiquidExtension::class]))
                ->filter(fn (mixed $extensionClass) => is_string($extensionClass) && class_exists($extensionClass))
                ->map(fn (string $extensionClass): Extension => $app->make($extensionClass))
                ->each(fn (Extension $extension) => $environment->addExtension($extension));

            return $environment;
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

        $this->registerClockwork();
    }

    protected function registerClockwork(): void
    {
        if (! class_exists(\Clockwork\Clockwork::class)) {
            return;
        }

        $this->app->singleton('clockwork.liquid', function (Application $app) {
            return new LiquidDataSource($app->make('liquid.environment'));
        });

        $this->callAfterResolving('clockwork', function (Clockwork $clockwork) {
            $dataSource = $this->app->make('clockwork.liquid');
            assert($dataSource instanceof LiquidDataSource);
            $clockwork->addDataSource($dataSource);
            $dataSource->listenToEvents();
        });
    }
}
