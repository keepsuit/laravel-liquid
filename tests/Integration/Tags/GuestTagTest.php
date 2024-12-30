<?php

use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('guest tag', function () {
    $template = $this->environment->parseString('{% guest %}guest{% endguest %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('');
});

it('auth tag with custom guard', function () {
    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);

    $template = $this->environment->parseString('{% guest "admin" %}guest{% endguest %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::guard('admin')->setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('');
});

it('guest tag else', function () {
    $template = $this->environment->parseString('{% guest %}guest{% else %}authenticated{% endguest %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('guest');

    Auth::setUser(new \Illuminate\Foundation\Auth\User);
    expect($template->render($this->environment->newRenderContext()))->toBe('authenticated');
});
