<?php

use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->factory = newLiquidFactory();
});

it('session tag missing value', function () {
    $template = $this->factory->parseString('{% session "status" %}present{% endsession %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('');
});

it('session tag has value', function () {
    $mock = Session::partialMock();
    $mock->shouldReceive('has', 'status')->andReturn(true);
    $mock->shouldReceive('get', 'status')->andReturn('ok');

    $template = $this->factory->parseString('{% session "status" %}present{% endsession %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('present');
});

it('session tag pass value to body', function () {
    $mock = Session::partialMock();
    $mock->shouldReceive('has', 'status')->andReturn(true);
    $mock->shouldReceive('get', 'status')->andReturn('ok');

    $template = $this->factory->parseString('{% session "status" %}value: {{ value}}{% endsession %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('value: ok');
});

it('session tag else', function () {
    $template = $this->factory->parseString('{% session "status" %}present{% else %}missing{% endsession %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('missing');
});
