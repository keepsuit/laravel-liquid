<?php

use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('guest tag', function (string $mode) {
    $template = parseLiquidString($this->environment, '{% guest %}guest{% endguest %}', $mode);

    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('');
})->with('liquid modes');

it('auth tag with custom guard', function (string $mode) {
    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);

    $template = parseLiquidString($this->environment, '{% guest "admin" %}guest{% endguest %}', $mode);

    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::guard('admin')->setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('');
})->with('liquid modes');

it('guest tag else', function (string $mode) {
    $template = parseLiquidString($this->environment, '{% guest %}guest{% else %}authenticated{% endguest %}', $mode);

    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('authenticated');
})->with('liquid modes');
