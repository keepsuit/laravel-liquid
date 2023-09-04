<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\LaravelLiquid\LiquidEngine;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\Liquid\TemplateFactory;

beforeEach(function () {
    $this->viewFinder = mock(FileViewFinder::class);
    $viewFactory = mock(Factory::class);
    $viewFactory->shouldReceive('getFinder')->andReturn($this->viewFinder);
    $this->files = mock(Filesystem::class);
    $this->app->bind(Factory::class, fn () => $viewFactory);
    $this->app->bind(LaravelLiquidFileSystem::class, fn () => new LaravelLiquidFileSystem(
        files: $this->files,
        viewFinder: $this->viewFinder,
    ));

    $this->engine = new LiquidEngine(
        new LiquidCompiler(
            files: $this->files,
            cachePath: __DIR__
        ),
        $this->files
    );
});

test('views may be recompiled and rerendered', function () {
    $template = TemplateFactory::new()->parseString('Hello World', 'fixtures.foo');
    $path = __DIR__.'/fixtures/foo.liquid';
    $cachePath = __DIR__.'/'.hash('xxh128', 'v2'.$path).'.php';

    $this->files->shouldReceive('exists')->once()->with($cachePath)->andReturn(true);
    $this->files->shouldReceive('lastModified')->andReturn(100);
    $this->files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
    $this->files->shouldReceive('get')->once()->with($path)->andReturn('Hello World');
    $this->files->shouldReceive('put')->once()->with($cachePath, serialize($template))->andReturn(true);
    $this->files->shouldReceive('get')->once()->with($cachePath)->andReturn(serialize($template));
    $this->viewFinder->shouldReceive('getViews')->once()->andReturn(['fixtures.foo' => $path]);
    $this->viewFinder->shouldReceive('find')->once()->with('fixtures.foo')->andReturn($path);

    $results = $this->engine->get($path);

    expect($results)->toBe('Hello World');
});
