<?php

use Illuminate\Container\Container;
use Keepsuit\Liquid\TemplateFactory;

beforeEach(function () {
    /** @var TemplateFactory factory */
    $this->factory = Container::getInstance()->make(TemplateFactory::class);
});

test('asset filter', function () {
    expect($this->factory->parseString('{{ "css/app.css" | asset }}')->render($this->factory->newRenderContext()))
        ->toBe('http://localhost/css/app.css');
});

test('secure asset filter', function () {
    expect($this->factory->parseString('{{ "css/app.css" | secure_asset }}')->render($this->factory->newRenderContext()))
        ->toBe('https://localhost/css/app.css');
});

test('route filter', function () {
    \Illuminate\Support\Facades\Route::get('home')->name('home');

    expect($this->factory->parseString('{{ "home" | route }}')->render($this->factory->newRenderContext()))
        ->toBe('http://localhost/home');
});

test('url filter', function () {
    expect($this->factory->parseString('{{ "user/profile" | url }}')->render($this->factory->newRenderContext()))
        ->toBe('http://localhost/user/profile');
});

test('secure url filter', function () {
    expect($this->factory->parseString('{{ "user/profile" | secure_url }}')->render($this->factory->newRenderContext()))
        ->toBe('https://localhost/user/profile');
});

test('route filter with params', function () {
    \Illuminate\Support\Facades\Route::get('products/{product}')->name('product');

    expect($this->factory->parseString('{{ "product" | route: product:1  }}')->render($this->factory->newRenderContext()))
        ->toBe('http://localhost/products/1');
    expect($this->factory->parseString('{{ "product" | route: 2  }}')->render($this->factory->newRenderContext()))
        ->toBe('http://localhost/products/2');
});
