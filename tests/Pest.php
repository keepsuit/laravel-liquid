<?php

use Keepsuit\LaravelLiquid\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function newLiquidEnvironment(): \Keepsuit\Liquid\Environment
{
    return app('liquid.factory')
        ->setRethrowErrors()
        ->setStrictVariables()
        ->setStrictFilters()
        ->build();
}

function setEnv(string $env): void
{
    app()->bind('env', fn () => $env);
    expect(app()->environment())->toBe($env);
}
