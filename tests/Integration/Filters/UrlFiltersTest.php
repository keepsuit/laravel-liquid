<?php

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

test('asset filter', function () {
    expect(parseLiquidString($this->environment, '{{ "css/app.css" | asset }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/css/app.css');
});

test('secure asset filter', function () {
    expect(parseLiquidString($this->environment, '{{ "css/app.css" | secure_asset }}')->render($this->environment->newRenderContext()))
        ->toBe('https://localhost/css/app.css');
});

test('route filter', function () {
    \Illuminate\Support\Facades\Route::get('home')->name('home');

    expect(parseLiquidString($this->environment, '{{ "home" | route }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/home');
});

test('url filter', function () {
    expect(parseLiquidString($this->environment, '{{ "user/profile" | url }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/user/profile');
});

test('secure url filter', function () {
    expect(parseLiquidString($this->environment, '{{ "user/profile" | secure_url }}')->render($this->environment->newRenderContext()))
        ->toBe('https://localhost/user/profile');
});

test('route filter with params', function () {
    \Illuminate\Support\Facades\Route::get('products/{product}')->name('product');

    expect(parseLiquidString($this->environment, '{{ "product" | route: product:1  }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/products/1');
    expect(parseLiquidString($this->environment, '{{ "product" | route: 2  }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/products/2');
});

test('vite asset filter', function () {
    makeViteManifest();

    expect(parseLiquidString($this->environment, '{{ "resources/assets/logo.png" | vite_asset }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/build/assets/logo-versioned.png');

    cleanViteManifest();
});

test('vite asset filter with custom directory', function () {
    makeViteManifest('custom');

    expect(parseLiquidString($this->environment, '{{ "resources/assets/logo.png" | vite_asset: "custom" }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/custom/assets/logo-versioned.png');

    cleanViteManifest('custom');
});
