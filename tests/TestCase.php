<?php

namespace Keepsuit\Liquid\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Keepsuit\Liquid\LiquidServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Keepsuit\\Liquid\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LiquidServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-liquid_table.php.stub';
        $migration->up();
        */
    }
}
