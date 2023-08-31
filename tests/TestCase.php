<?php

namespace Keepsuit\Liquid\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Keepsuit\Liquid\LiquidServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LiquidServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
    }
}
