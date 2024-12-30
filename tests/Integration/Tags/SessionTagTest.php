<?php

use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('session tag missing value', function () {
    $template = $this->environment->parseString('{% session "status" %}present{% endsession %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('');
});

it('session tag has value', function () {
    $mock = Session::partialMock();
    $mock->shouldReceive('has', 'status')->andReturn(true);
    $mock->shouldReceive('get', 'status')->andReturn('ok');

    $template = $this->environment->parseString('{% session "status" %}present{% endsession %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('present');
});

it('session tag pass value to body', function () {
    $mock = Session::partialMock();
    $mock->shouldReceive('has', 'status')->andReturn(true);
    $mock->shouldReceive('get', 'status')->andReturn('ok');

    $template = $this->environment->parseString('{% session "status" %}value: {{ value}}{% endsession %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('value: ok');
});

it('session tag else', function () {
    $template = $this->environment->parseString('{% session "status" %}present{% else %}missing{% endsession %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('missing');
});
