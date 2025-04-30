<?php

beforeEach(function () {
    makeViteManifest();
    app('config')->set('app.asset_url', 'https://example.com');

    $this->environment = newLiquidEnvironment();
});

afterEach(function () {
    cleanViteManifest();
});

test('vite tag with single js entrypoint', function () {
    $template = $this->environment->parseString('{% vite "resources/js/app.js" %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('<link rel="modulepreload" href="https://example.com/build/assets/app.versioned.js" /><script type="module" src="https://example.com/build/assets/app.versioned.js"></script>');
});

test('vite tag with single css entrypoint', function () {
    $template = $this->environment->parseString('{% vite "resources/css/app.css" %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('<link rel="preload" as="style" href="https://example.com/build/assets/app.versioned.css" /><link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" />');
});

test('vite tag with single multiple entrypoints', function () {
    $template = $this->environment->parseString('{% vite "resources/css/app.css", "resources/js/app.js" %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('<link rel="preload" as="style" href="https://example.com/build/assets/app.versioned.css" /><link rel="modulepreload" href="https://example.com/build/assets/app.versioned.js" /><link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" /><script type="module" src="https://example.com/build/assets/app.versioned.js"></script>');
});

test('vite tag with single entrypoint and custom directory', function () {
    makeViteManifest('custom');

    $template = $this->environment->parseString('{% vite "resources/js/app.js", directory: "custom" %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('<link rel="modulepreload" href="https://example.com/custom/assets/app.versioned.js" /><script type="module" src="https://example.com/custom/assets/app.versioned.js"></script>');

    cleanViteManifest('custom');
});

test('vite tag with multiple entrypoints and custom directory', function () {
    makeViteManifest('custom');

    $template = $this->environment->parseString('{% vite "resources/css/app.css", "resources/js/app.js", directory: "custom" %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('<link rel="preload" as="style" href="https://example.com/custom/assets/app.versioned.css" /><link rel="modulepreload" href="https://example.com/custom/assets/app.versioned.js" /><link rel="stylesheet" href="https://example.com/custom/assets/app.versioned.css" /><script type="module" src="https://example.com/custom/assets/app.versioned.js"></script>');

    cleanViteManifest('custom');
});

test('vite tag exports entrypoints after parsing', function () {
    $template = $this->environment->parseString('{% vite "resources/js/app.js" %}');

    $outputs = $template->getState()->outputs->all();

    expect($outputs)
        ->toHaveKey('vite_entrypoints')
        ->not->toHaveKey('vite_preloads');

    expect($outputs['vite_entrypoints'])->toBe([
        [
            'entrypoint' => 'resources/js/app.js',
        ],
    ]);
});

test('vite tag exports entrypoints after parsing with custom directory', function () {
    $template = $this->environment->parseString('{% vite "resources/js/app.js", directory: "custom" %}');

    $outputs = $template->getState()->outputs->all();

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
    $template = $this->environment->parseString('{% vite "resources/js/app.js" %}');
    $template->render($this->environment->newRenderContext());

    $outputs = $template->getState()->outputs->all();

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

test('vite tag exports preloads after rendering with custom directory', function () {
    makeViteManifest('custom');

    $template = $this->environment->parseString('{% vite "resources/js/app.js", directory: "custom" %}');
    $template->render($this->environment->newRenderContext());

    $outputs = $template->getState()->outputs->all();

    expect($outputs)
        ->toHaveKey('vite_entrypoints')
        ->toHaveKey('vite_preloads');

    expect($outputs['vite_preloads'])->toBe([
        [
            'href' => 'https://example.com/custom/assets/app.versioned.js',
            'attributes' => [
                'rel="modulepreload"',
            ],
            'directory' => 'custom',
        ],
    ]);

    cleanViteManifest('custom');
});
