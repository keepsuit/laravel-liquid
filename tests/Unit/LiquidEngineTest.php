<?php

use Illuminate\Filesystem\Filesystem;
use Keepsuit\Liquid\LiquidCompiler;
use Keepsuit\Liquid\LiquidEngine;
use Keepsuit\Liquid\TemplateFactory;

beforeEach(function () {
    $this->files = mock(Filesystem::class);
    $this->engine = new LiquidEngine(
        new LiquidCompiler(
            files: $this->files,
            cachePath: __DIR__
        ),
        $this->files
    );
});

test('views may be recompiled and rerendered', function () {
    $template = TemplateFactory::new()->parse('Hello World');
    $cachePath = __DIR__.'/'.hash('xxh128', 'v2'.__DIR__.'/fixtures/foo.liquid').'.php';

    $this->files->shouldReceive('exists')->once()->with($cachePath)->andReturn(true);
    $this->files->shouldReceive('lastModified')->andReturn(100);
    $this->files->shouldReceive('exists')->once()->with(__DIR__)->andReturn(true);
    $this->files->shouldReceive('get')->once()->with(__DIR__.'/fixtures/foo.liquid')->andReturn('Hello World');
    $this->files->shouldReceive('put')->once()->with($cachePath, serialize($template))->andReturn(true);
    $this->files->shouldReceive('get')->once()->with($cachePath)->andReturn(serialize($template));

    $results = $this->engine->get(__DIR__.'/fixtures/foo.liquid');

    expect($results)->toBe('Hello World');
});
