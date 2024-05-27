<?php

beforeEach(function () {
    makeViteManifest();
    app('config')->set('app.asset_url', 'https://example.com');

    $this->factory = newLiquidFactory();
});

afterEach(function () {
    cleanViteManifest();
});

test('vite tag with single js entrypoint', function () {
    $template = $this->factory->parseString('{% vite "resources/js/app.js" %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('<link rel="modulepreload" href="https://example.com/build/assets/app.versioned.js" /><script type="module" src="https://example.com/build/assets/app.versioned.js"></script>');
});

test('vite tag with single css entrypoint', function () {
    $template = $this->factory->parseString('{% vite "resources/css/app.css" %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('<link rel="preload" as="style" href="https://example.com/build/assets/app.versioned.css" /><link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" />');
});

test('vite tag with single multiple entrypoints', function () {
    $template = $this->factory->parseString('{% vite "resources/css/app.css", "resources/js/app.js" %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('<link rel="preload" as="style" href="https://example.com/build/assets/app.versioned.css" /><link rel="modulepreload" href="https://example.com/build/assets/app.versioned.js" /><link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" /><script type="module" src="https://example.com/build/assets/app.versioned.js"></script>');
});

test('vite tag with single entrypoint and custom directory', function () {
    makeViteManifest('custom');

    $template = $this->factory->parseString('{% vite "resources/js/app.js", directory: "custom" %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('<link rel="modulepreload" href="https://example.com/custom/assets/app.versioned.js" /><script type="module" src="https://example.com/custom/assets/app.versioned.js"></script>');

    cleanViteManifest('custom');
});

test('vite tag with multiple entrypoints and custom directory', function () {
    makeViteManifest('custom');

    $template = $this->factory->parseString('{% vite "resources/css/app.css", "resources/js/app.js", directory: "custom" %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('<link rel="preload" as="style" href="https://example.com/custom/assets/app.versioned.css" /><link rel="modulepreload" href="https://example.com/custom/assets/app.versioned.js" /><link rel="stylesheet" href="https://example.com/custom/assets/app.versioned.css" /><script type="module" src="https://example.com/custom/assets/app.versioned.js"></script>');

    cleanViteManifest('custom');
});

test('vite tag exports entrypoints after parsing', function () {
    $template = $this->factory->parseString('{% vite "resources/js/app.js", directory: "custom" %}');

    $outputs = $template->getState()->outputs;

    expect($outputs)
        ->toHaveKey('vite_entrypoints')
        ->not->toHaveKey('vite_preloads');

    expect($outputs['vite_entrypoints'])->toBe([
        [
            'entrypoint' => 'resources/js/app.js',
            'directory' => 'custom',
        ],
    ]);
});

test('vite tag exports preloads after rendering', function () {
    makeViteManifest();

    $template = $this->factory->parseString('{% vite "resources/js/app.js" %}');
    $template->render($this->factory->newRenderContext());

    $outputs = $template->getState()->outputs;

    expect($outputs)
        ->toHaveKey('vite_entrypoints')
        ->toHaveKey('vite_preloads');

    expect($outputs['vite_preloads'])->toBe([
        [
            'href' => 'https://example.com/build/assets/app.versioned.js',
            'attributes' => [
                'rel="modulepreload"',
            ],
        ],
    ]);

    cleanViteManifest();
});

function makeViteManifest($path = 'build')
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
        //        'resources/js/app-with-css-import.js' => [
        //            'src' => 'resources/js/app-with-css-import.js',
        //            'file' => 'assets/app-with-css-import.versioned.js',
        //            'css' => [
        //                'assets/imported-css.versioned.css',
        //            ],
        //        ],
        //        'resources/css/imported-css.css' => [
        //            // 'src' => 'resources/css/imported-css.css',
        //            'file' => 'assets/imported-css.versioned.css',
        //        ],
        //        'resources/js/app-with-shared-css.js' => [
        //            'src' => 'resources/js/app-with-shared-css.js',
        //            'file' => 'assets/app-with-shared-css.versioned.js',
        //            'imports' => [
        //                '_someFile.js',
        //            ],
        //        ],
        'resources/css/app.css' => [
            'src' => 'resources/css/app.css',
            'file' => 'assets/app.versioned.css',
        ],
        //        '_someFile.js' => [
        //            'file' => 'assets/someFile.versioned.js',
        //            'css' => [
        //                'assets/shared-css.versioned.css',
        //            ],
        //        ],
        //        'resources/css/shared-css' => [
        //            'src' => 'resources/css/shared-css',
        //            'file' => 'assets/shared-css.versioned.css',
        //        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    file_put_contents(public_path("{$path}/manifest.json"), $manifest);
}

function cleanViteManifest($path = 'build')
{
    if (file_exists(public_path("{$path}/manifest.json"))) {
        unlink(public_path("{$path}/manifest.json"));
    }

    if (file_exists(public_path($path))) {
        rmdir(public_path($path));
    }
}
