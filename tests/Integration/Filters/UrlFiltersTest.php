<?php

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

test('asset filter', function () {
    expect($this->environment->parseString('{{ "css/app.css" | asset }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/css/app.css');
});

test('secure asset filter', function () {
    expect($this->environment->parseString('{{ "css/app.css" | secure_asset }}')->render($this->environment->newRenderContext()))
        ->toBe('https://localhost/css/app.css');
});

test('route filter', function () {
    \Illuminate\Support\Facades\Route::get('home')->name('home');

    expect($this->environment->parseString('{{ "home" | route }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/home');
});

test('url filter', function () {
    expect($this->environment->parseString('{{ "user/profile" | url }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/user/profile');
});

test('secure url filter', function () {
    expect($this->environment->parseString('{{ "user/profile" | secure_url }}')->render($this->environment->newRenderContext()))
        ->toBe('https://localhost/user/profile');
});

test('route filter with params', function () {
    \Illuminate\Support\Facades\Route::get('products/{product}')->name('product');

    expect($this->environment->parseString('{{ "product" | route: product:1  }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/products/1');
    expect($this->environment->parseString('{{ "product" | route: 2  }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/products/2');
});

test('vite asset filter', function () {
    makeViteManifest();

    expect($this->environment->parseString('{{ "resources/assets/logo.png" | vite_asset }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/build/assets/logo-versioned.png');

    cleanViteManifest();
});

test('vite asset filter with custom directory', function () {
    makeViteManifest('custom');

    expect($this->environment->parseString('{{ "resources/assets/logo.png" | vite_asset: "custom" }}')->render($this->environment->newRenderContext()))
        ->toBe('http://localhost/custom/assets/logo-versioned.png');

    cleanViteManifest('custom');
});
