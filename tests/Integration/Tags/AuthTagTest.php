<?php

use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('auth tag', function () {
    $template = parseLiquidString($this->environment, '{% auth %}authenticated{% endauth %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('authenticated');
});

it('auth tag with custom guard', function () {
    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);

    $template = parseLiquidString($this->environment, '{% auth "admin" %}authenticated{% endauth %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('');

    Auth::guard('admin')->setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('authenticated');
});

it('auth tag else', function () {
    $template = parseLiquidString($this->environment, '{% auth %}authenticated{% else %}guest{% endauth %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('authenticated');
});
