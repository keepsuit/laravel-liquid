<?php

use Illuminate\Filesystem\Filesystem;
use Keepsuit\Liquid\LiquidViewCompiler;
use Keepsuit\Liquid\TemplateFactory;

beforeEach(function () {
    $this->compiler = new LiquidViewCompiler(
        files: $this->files = mock(Filesystem::class),
        cachePath: __DIR__
    );
});

test('isExpired returns true if compiled file doesnt exist', function () {
    $this->files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(false);

    expect($this->compiler->isExpired('foo'))->toBeTrue();
});

test('isExpired return true when modification times warrant', function () {
    $this->files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(true);
    $this->files->shouldReceive('lastModified')->once()->with('foo')->andReturn(100);
    $this->files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(0);

    expect($this->compiler->isExpired('foo'))->toBeTrue();
});

test('isExpired return false when cache is true and no file modification', function () {
    $this->files->shouldReceive('exists')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(true);
    $this->files->shouldReceive('lastModified')->once()->with('foo')->andReturn(0);
    $this->files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php')->andReturn(100);

    expect($this->compiler->isExpired('foo'))->toBeFalse();
});

test('compiles file and returns content', function () {
    $template = TemplateFactory::new()->parse('Hello World');

    $this->files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
    $this->files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
    $this->files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php', serialize($template));

    $this->compiler->compile('foo');
});

test('compiles file and returns content creating directory', function () {
    $template = TemplateFactory::new()->parse('Hello World');

    $this->files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
    $this->files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(false);
    $this->files->shouldReceive('makeDirectory')->once()->with(__DIR__, 0777, true, true);
    $this->files->shouldReceive('put')->once()->with(__DIR__.'/'.hash('xxh128', 'v2foo').'.php', serialize($template));

    $this->compiler->compile('foo');
});

test('isExpired return false when use cache is false', function () {
    $compiler = new LiquidViewCompiler(
        files: $this->files,
        cachePath: __DIR__,
        shouldCache: false,
    );

    expect($compiler->isExpired('foo'))->toBeTrue();
});
