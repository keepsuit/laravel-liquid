<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Keepsuit\LaravelLiquid\Facades\Liquid;
use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\LaravelLiquid\LiquidEngine;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\LaravelLiquid\Support\LaravelTemplatesCache;

beforeEach(function () {
    $this->viewFinder = mock(FileViewFinder::class);
    $viewFactory = mock(Factory::class);
    $viewFactory->shouldReceive('getFinder')->andReturn($this->viewFinder);
    $this->files = mock(Filesystem::class);

    $compiler = new LiquidCompiler(
        files: $this->files,
        cachePath: __DIR__
    );
    $this->engine = new LiquidEngine(
        $compiler,
        $this->files
    );

    $this->app->bind(\Illuminate\Contracts\View\Factory::class, fn () => $viewFactory);
    $this->app->bind('liquid.environment', fn () => $this->app->make('liquid.factory')
        ->setTemplatesCache(new LaravelTemplatesCache($compiler))
        ->setFilesystem(new LaravelLiquidFileSystem($compiler))
        ->build());
});

test('views may be recompiled and rerendered', function () {
    $template = Liquid::environment()->parseString('Hello World', 'fixtures.foo');
    $path = __DIR__.'/fixtures/foo.liquid';
    $compiledPath = __DIR__.'/'.hash('xxh128', 'v2'.$path).'.php';

    $this->viewFinder->shouldReceive('getViews')->once()->andReturn(['fixtures.foo' => $path]);
    $this->viewFinder->shouldReceive('find')->with('fixtures.foo')->andReturn($path);

    $this->files->shouldReceive('exists')->with($compiledPath)->andReturn(true);
    $this->files->shouldReceive('lastModified')->andReturn(100);

    $this->files->shouldReceive('get')->with($compiledPath)->andReturn(serialize($template));
    $this->files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
    $this->files->shouldReceive('put')->once()->with($compiledPath, serialize($template))->andReturn(true);
    $this->files->shouldReceive('get')->once()->with($path)->andReturn('Hello World');

    $results = $this->engine->get($path);

    expect($results)->toBe('Hello World');
});
