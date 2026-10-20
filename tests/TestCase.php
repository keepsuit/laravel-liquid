<?php

namespace Keepsuit\LaravelLiquid\Tests;

use Keepsuit\LaravelLiquid\LiquidServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class TestCase extends Orchestra
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    protected function tearDown(): void
    {
        $this->temporaryDirectory?->delete();
        $this->temporaryDirectory = null;

        parent::tearDown();
    }

    public function temporaryDirectory(): TemporaryDirectory
    {
        return $this->temporaryDirectory ??= (new TemporaryDirectory(realpath(sys_get_temp_dir())))->create();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LiquidServiceProvider::class,
        ];
    }

    public function defineEnvironment($app): void
    {
        $app['config']->set('view.paths', [__DIR__.'/fixtures']);
        $app['config']->set('view.cache', false);

        $app->forgetInstance('view');
    }
}
