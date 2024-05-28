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
