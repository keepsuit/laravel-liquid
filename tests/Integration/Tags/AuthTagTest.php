<?php

use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('auth tag', function () {
    $template = $this->environment->parseString('{% auth %}authenticated{% endauth %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('authenticated');
});

it('auth tag with custom guard', function () {
    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);

    $template = $this->environment->parseString('{% auth "admin" %}authenticated{% endauth %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('');

    Auth::guard('admin')->setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('authenticated');
});

it('auth tag else', function () {
    $template = $this->environment->parseString('{% auth %}authenticated{% else %}guest{% endauth %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('authenticated');
});
