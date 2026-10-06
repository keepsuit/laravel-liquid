<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\LaravelLiquid\Support\LaravelTemplatesCache;
use Keepsuit\Liquid\Compiler\CompiledTemplate;

beforeEach(function () {
    $this->viewFinder = mock(FileViewFinder::class);
    $viewFactory = mock(Factory::class);
    $viewFactory->shouldReceive('getFinder')->andReturn($this->viewFinder);
    $this->files = mock(Filesystem::class)->makePartial();
    $this->compiler = new LiquidCompiler(
        files: $this->files,
        cachePath: $this->cacheDir = __DIR__.'/../cache',
    );

    $this->files->deleteDirectory($this->cacheDir);

    $this->app->bind(\Illuminate\Contracts\View\Factory::class, fn () => $viewFactory);
    $this->app->bind('liquid.environment', fn () => $this->app->make('liquid.factory')
        ->setTemplatesCache(new LaravelTemplatesCache($this->compiler))
        ->setFilesystem(new LaravelLiquidFileSystem($this->compiler))
        ->build());
});

afterEach(function () {
    $this->files->deleteDirectory($this->cacheDir);
});

test('isExpired returns true if compiled file doesnt exist', function () {
    $this->files->shouldReceive('exists')->once()->with($this->cacheDir.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(false);

    expect($this->compiler->isExpired('foo'))->toBeTrue();
});

test('isExpired return true when modification times warrant', function () {
    $this->files->shouldReceive('exists')->once()->with($this->cacheDir.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(true);
    $this->files->shouldReceive('lastModified')->once()->with('foo')->andReturn(100);
    $this->files->shouldReceive('lastModified')->once()->with($this->cacheDir.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(0);

    expect($this->compiler->isExpired('foo'))->toBeTrue();
});

test('isExpired return false when cache is true and no file modification', function () {
    $this->files->shouldReceive('exists')->once()->with($this->cacheDir.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(true);
    $this->files->shouldReceive('lastModified')->once()->with('foo')->andReturn(0);
    $this->files->shouldReceive('lastModified')->once()->with($this->cacheDir.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(100);

    expect($this->compiler->isExpired('foo'))->toBeFalse();
});

test('compiles PHP artifacts with existing or missing cache directories', function (bool $existingDirectory) {
    if ($existingDirectory) {
        $this->files->makeDirectory($this->cacheDir, 0755, true);
    }

    $compiledPath = $this->compiler->getCompiledPath('foo.liquid');
    $this->viewFinder->shouldReceive('getViews')->once()->andReturn(['foo' => 'foo.liquid']);
    $this->viewFinder->shouldReceive('find')->with('foo')->andReturn('foo.liquid');
    $this->files->shouldReceive('get')->once()->with('foo.liquid')->andReturn('Hello {{ name }}');

    $this->compiler->compile('foo.liquid');

    $template = require $compiledPath;
    expect($template)->toBeInstanceOf(CompiledTemplate::class);
    expect($template->render(app('liquid.environment')->newRenderContext(data: ['name' => 'World'])))
        ->toBe('Hello World');
})->with([true, false]);

test('isExpired return false when use cache is false', function () {
    $compiler = new LiquidCompiler(
        files: $this->files,
        cachePath: __DIR__.'/../cache',
        shouldCache: false,
    );

    expect($compiler->isExpired('foo'))->toBeTrue();
});
