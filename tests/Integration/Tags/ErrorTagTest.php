<?php

use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('error tag valid', function () {
    $template = $this->environment->parseString('{% error "name" %}name is required{% enderror %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('');
});

it('error tag invalid', function () {
    $errorBag = new \Illuminate\Support\ViewErrorBag;
    $errorBag->put('default', new \Illuminate\Support\MessageBag(['name' => 'name is required']));
    $mock = Session::partialMock();
    $mock->shouldReceive('get', 'errors')->andReturn($errorBag);

    $template = $this->environment->parseString('{% error "name" %}name is required{% enderror %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('name is required');
});

it('error tag invalid pass message to body', function () {
    $errorBag = new \Illuminate\Support\ViewErrorBag;
    $errorBag->put('default', new \Illuminate\Support\MessageBag(['name' => 'name is required']));
    $mock = Session::partialMock();
    $mock->shouldReceive('get', 'errors')->andReturn($errorBag);

    $template = $this->environment->parseString('{% error "name" %}message: {{message}}{% enderror %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('message: name is required');
});

it('error tag custom bag', function () {
    $errorBag = new \Illuminate\Support\ViewErrorBag;
    $errorBag->put('custom', new \Illuminate\Support\MessageBag(['name' => 'name is required']));
    $mock = Session::partialMock();
    $mock->shouldReceive('get', 'errors')->andReturn($errorBag);

    $template = $this->environment->parseString('{% error "name", bag: "custom" %}message: {{message}}{% enderror %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('message: name is required');
});
