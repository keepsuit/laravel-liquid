<?php

use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->factory = newLiquidFactory();
});

it('error tag valid', function () {
    $template = $this->factory->parseString('{% error "name" %}name is required{% enderror %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('');
});

it('error tag invalid', function () {
    $errorBag = new \Illuminate\Support\ViewErrorBag;
    $errorBag->put('default', new \Illuminate\Support\MessageBag(['name' => 'name is required']));
    $mock = Session::partialMock();
    $mock->shouldReceive('get', 'errors')->andReturn($errorBag);

    $template = $this->factory->parseString('{% error "name" %}name is required{% enderror %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('name is required');
});

it('error tag invalid pass message to body', function () {
    $errorBag = new \Illuminate\Support\ViewErrorBag;
    $errorBag->put('default', new \Illuminate\Support\MessageBag(['name' => 'name is required']));
    $mock = Session::partialMock();
    $mock->shouldReceive('get', 'errors')->andReturn($errorBag);

    $template = $this->factory->parseString('{% error "name" %}message: {{message}}{% enderror %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('message: name is required');
});

it('error tag custom bag', function () {
    $errorBag = new \Illuminate\Support\ViewErrorBag;
    $errorBag->put('custom', new \Illuminate\Support\MessageBag(['name' => 'name is required']));
    $mock = Session::partialMock();
    $mock->shouldReceive('get', 'errors')->andReturn($errorBag);

    $template = $this->factory->parseString('{% error "name", bag: "custom" %}message: {{message}}{% enderror %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('message: name is required');
});
