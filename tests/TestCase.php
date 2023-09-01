<?php

namespace Keepsuit\Liquid\Tests;

use Keepsuit\Liquid\LiquidServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
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
