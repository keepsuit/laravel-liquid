<?php

use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->factory = newLiquidFactory();
});

it('auth tag', function () {
    $template = $this->factory->parseString('{% auth %}authenticated{% endauth %}');

    expect($template->render($this->factory->newRenderContext()))->toBe('');

    Auth::setUser(new \Illuminate\Foundation\Auth\User());
    expect($template->render($this->factory->newRenderContext()))->toBe('authenticated');
});

it('auth tag with custom guard', function () {
    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);

    $template = $this->factory->parseString('{% auth "admin" %}authenticated{% endauth %}');

    expect($template->render($this->factory->newRenderContext()))->toBe('');

    Auth::setUser(new \Illuminate\Foundation\Auth\User());
    expect($template->render($this->factory->newRenderContext()))->toBe('');

    Auth::guard('admin')->setUser(new \Illuminate\Foundation\Auth\User());
    expect($template->render($this->factory->newRenderContext()))->toBe('authenticated');
});
