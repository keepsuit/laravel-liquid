<?php

it('renders simple liquid template', function () {
    expect(trim(view('simple')->render()))->toBe(<<<'HTML'
    Hello world
    HTML
    );
});

it('renders liquid template with partial', function () {
    expect(trim(view('render')->render()))->toBe(<<<'HTML'
    main
    Hello world
    HTML
    );
});

it('renders liquid template with facade', function () {
    expect(trim(\Keepsuit\LaravelLiquid\Facades\Liquid::render('simple')))->toBe(<<<'HTML'
    Hello world
    HTML
    );
});
