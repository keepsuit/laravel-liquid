<?php

beforeEach(function () {
    $this->factory = newLiquidFactory();
});

it('env tag', function () {
    $template = $this->factory->parseString('{% env "production" %}prod{% endenv %}');

    expect($template->render($this->factory->newRenderContext()))->toBe('');

    setEnv('production');
    expect($template->render($this->factory->newRenderContext()))->toBe('prod');
});

it('env tag multiple', function () {
    $template = $this->factory->parseString('{% env "production", "staging" %}staging or production{% endenv %}');

    expect($template->render($this->factory->newRenderContext()))->toBe('');

    setEnv('production');
    expect($template->render($this->factory->newRenderContext()))->toBe('staging or production');

    setEnv('staging');
    expect($template->render($this->factory->newRenderContext()))->toBe('staging or production');
});

it('env tag else ', function () {
    $template = $this->factory->parseString('{% env "production" %}prod{% else %}dev{% endenv %}');

    expect($template->render($this->factory->newRenderContext()))->toBe('dev');

    setEnv('production');
    expect($template->render($this->factory->newRenderContext()))->toBe('prod');
});
