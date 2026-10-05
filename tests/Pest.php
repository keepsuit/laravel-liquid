<?php

use Keepsuit\LaravelLiquid\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function newLiquidEnvironment(): \Keepsuit\Liquid\Environment
{
    return app('liquid.factory')
        ->setRethrowErrors(true)
        ->setStrictVariables(true)
        ->setStrictFilters(true)
        ->build();
}

function parseLiquidString(\Keepsuit\Liquid\Environment $environment, string $source): \Keepsuit\Liquid\Template
{
    $template = $environment->parseString($source);

    $path = sys_get_temp_dir().'/laravel-liquid-test-'.bin2hex(random_bytes(8)).'.php';

    try {
        $environment->compile($template, $path);
        $compiled = require $path;
        $class = $compiled::class;

        return new $class($template->getState());
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
}

function setEnv(string $env): void
{
    app()->bind('env', fn () => $env);
    expect(app()->environment())->toBe($env);
}

function makeViteManifest(string $path = 'build'): void
{
    app()->usePublicPath(__DIR__);

    if (! file_exists(public_path($path))) {
        mkdir(public_path($path));
    }

    $manifest = json_encode($contents ?? [
        'resources/js/app.js' => [
            'src' => 'resources/js/app.js',
            'file' => 'assets/app.versioned.js',
        ],
        'resources/css/app.css' => [
            'src' => 'resources/css/app.css',
            'file' => 'assets/app.versioned.css',
        ],
        'resources/assets/logo.png' => [
            'file' => 'assets/logo-versioned.png',
            'src' => 'resources/assets/logo.png',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    file_put_contents(public_path("{$path}/manifest.json"), $manifest);
}

function cleanViteManifest(string $path = 'build'): void
{
    if (file_exists(public_path("{$path}/manifest.json"))) {
        unlink(public_path("{$path}/manifest.json"));
    }

    if (file_exists(public_path($path))) {
        rmdir(public_path($path));
    }
}
