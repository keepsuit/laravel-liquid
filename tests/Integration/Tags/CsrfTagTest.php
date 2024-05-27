<?php

beforeEach(function () {
    $this->factory = newLiquidFactory();
});

it('renders csrf input', function () {
    \Illuminate\Support\Facades\Session::partialMock()
        ->shouldReceive('token')
        ->andReturn('csrf-token-value');

    $template = $this->factory->parseString('{% csrf %}');

    expect($template->render($this->factory->newRenderContext()))
        ->toBe('<input type="hidden" name="_token" value="csrf-token-value" autocomplete="off">');
});
