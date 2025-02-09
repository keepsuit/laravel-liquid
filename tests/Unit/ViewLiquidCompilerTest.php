<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Keepsuit\LaravelLiquid\Facades\Liquid;
use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\LaravelLiquid\Support\LaravelTemplatesCache;
use Symfony\Component\VarExporter\VarExporter;

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

test('compiles file and returns content', function () {
    $template = Liquid::environment()->parseString('Hello World', 'foo');
    $compiledPath = $this->cacheDir.'/'.hash('xxh128', 'v2foo.liquid').'.php';

    $this->viewFinder->shouldReceive('getViews')->once()->andReturn(['foo' => 'foo.liquid']);
    $this->viewFinder->shouldReceive('find')->with('foo')->andReturn('foo.liquid');

    $this->files->shouldReceive('exists')->once()->with($compiledPath)->andReturn(null);
    $this->files->shouldReceive('get')->once()->with('foo.liquid')->andReturn('Hello World');

    $this->files->shouldReceive('exists')->once()->with($this->cacheDir)->andReturn(true);
    $this->files->shouldReceive('put')->once()->with($compiledPath, '<?php return '.VarExporter::export($template).';');

    $this->compiler->compile('foo.liquid');
});

test('compiles file and returns content creating directory', function () {
    $template = Liquid::environment()->parseString('Hello World', 'foo');
    $compiledPath = $this->cacheDir.'/'.hash('xxh128', 'v2foo.liquid').'.php';

    $this->viewFinder->shouldReceive('getViews')->once()->andReturn(['foo' => 'foo.liquid']);
    $this->viewFinder->shouldReceive('find')->with('foo')->andReturn('foo.liquid');

    $this->files->shouldReceive('exists')->once()->with($compiledPath)->andReturn(null);
    $this->files->shouldReceive('get')->once()->with('foo.liquid')->andReturn('Hello World');
    $this->files->shouldReceive('exists')->once()->with($this->cacheDir)->andReturn(false);
    $this->files->shouldReceive('makeDirectory')->once()->with($this->cacheDir, 0777, true, true);
    $this->files->shouldReceive('put')->once()->with($compiledPath, '<?php return '.VarExporter::export($template).';');

    $this->compiler->compile('foo.liquid');
});

test('isExpired return false when use cache is false', function () {
    $compiler = new LiquidCompiler(
        files: $this->files,
        cachePath: __DIR__.'/../cache',
        shouldCache: false,
    );

    expect($compiler->isExpired('foo'))->toBeTrue();
});
