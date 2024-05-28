<?php

use Keepsuit\LaravelLiquid\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function newLiquidFactory(): \Keepsuit\Liquid\TemplateFactory
{
    return app(\Keepsuit\Liquid\TemplateFactory::class)
        ->setRethrowExceptions()
        ->setStrictVariables();
}

function setEnv(string $env): void
{
    app()->bind('env', fn () => $env);
    expect(app()->environment())->toBe($env);
}
