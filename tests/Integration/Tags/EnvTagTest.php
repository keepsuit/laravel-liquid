<?php

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('env tag', function () {
    $template = $this->environment->parseString('{% env "production" %}prod{% endenv %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('');

    setEnv('production');
    expect($template->render($this->environment->newRenderContext()))->toBe('prod');
});

it('env tag multiple', function () {
    $template = $this->environment->parseString('{% env "production", "staging" %}staging or production{% endenv %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('');

    setEnv('production');
    expect($template->render($this->environment->newRenderContext()))->toBe('staging or production');

    setEnv('staging');
    expect($template->render($this->environment->newRenderContext()))->toBe('staging or production');
});

it('env tag else ', function () {
    $template = $this->environment->parseString('{% env "production" %}prod{% else %}dev{% endenv %}');

    expect($template->render($this->environment->newRenderContext()))->toBe('dev');

    setEnv('production');
    expect($template->render($this->environment->newRenderContext()))->toBe('prod');
});
