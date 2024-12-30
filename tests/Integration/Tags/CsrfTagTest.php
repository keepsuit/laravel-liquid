<?php

beforeEach(function () {
    $this->environment = newLiquidEnvironment();
});

it('renders csrf input', function () {
    \Illuminate\Support\Facades\Session::partialMock()
        ->shouldReceive('token')
        ->andReturn('csrf-token-value');

    $template = $this->environment->parseString('{% csrf %}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('<input type="hidden" name="_token" value="csrf-token-value" autocomplete="off">');
});
