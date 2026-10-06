<?php

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('env tag', function (string $mode) {
    $template = parseLiquidString($this->environment, '{% env "production" %}prod{% endenv %}', $mode);

    expect($template->render($this->environment->newRenderContext()))->toBe('');

    setEnv('production');
    expect($template->render($this->environment->newRenderContext()))->toBe('prod');
})->with('liquid modes');

it('env tag multiple', function (string $mode) {
    $template = parseLiquidString($this->environment, '{% env "production", "staging" %}staging or production{% endenv %}', $mode);

    expect($template->render($this->environment->newRenderContext()))->toBe('');

    setEnv('production');
    expect($template->render($this->environment->newRenderContext()))->toBe('staging or production');

    setEnv('staging');
    expect($template->render($this->environment->newRenderContext()))->toBe('staging or production');
})->with('liquid modes');

it('env tag else ', function (string $mode) {
    $template = parseLiquidString($this->environment, '{% env "production" %}prod{% else %}dev{% endenv %}', $mode);

    expect($template->render($this->environment->newRenderContext()))->toBe('dev');

    setEnv('production');
    expect($template->render($this->environment->newRenderContext()))->toBe('prod');
})->with('liquid modes');
