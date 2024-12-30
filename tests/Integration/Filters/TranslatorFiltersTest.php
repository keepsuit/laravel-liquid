<?php

beforeEach(function () {
    $this->environment = newLiquidEnvironment();

    /** @var \Illuminate\Translation\Translator $translator */
    $translator = app('translator');
    $translator->addLines([
        'home.title' => 'Home page title',
        'home.counter' => ':value items',
        'home.choice' => '{0} No items|{1} :count item|[2,*] :count items',
        'home.choice_param' => '{0} No items|{1} :value item|[2,*] :value items',
    ], 'en');
    $translator->addLines([
        'home.title' => 'Titolo della pagina principale',
        'home.counter' => ':value elementi',
        'home.choice' => '{0} Nessun elemento|{1} :count elemento|[2,*] :count elementi',
        'home.choice_param' => '{0} Nessun elemento|{1} :value elemento|[2,*] :value elementi',
    ], 'it');
});

test('trans filter', function () {
    $template = $this->environment->parseString('{{ "home.title" | trans }}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('Home page title');

    app()->setLocale('it');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('Titolo della pagina principale');
});

test('t filter alias', function () {
    $template = $this->environment->parseString('{{ "home.title" | t }}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('Home page title');

    app()->setLocale('it');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('Titolo della pagina principale');
});

test('trans filter with param', function () {
    $template = $this->environment->parseString('{{ "home.counter" | trans: value: 3 }}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('3 items');

    app()->setLocale('it');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('3 elementi');
});

test('trans_choice filter', function () {
    $template = $this->environment->parseString('{{ "home.choice" | trans_choice: 0 }}|{{ "home.choice" | trans_choice: 1 }}|{{ "home.choice" | trans_choice: 2 }}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('No items|1 item|2 items');

    app()->setLocale('it');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('Nessun elemento|1 elemento|2 elementi');
});

test('trans_choice filter with param', function () {
    $template = $this->environment->parseString('{{ "home.choice_param" | trans_choice: 0, value: 9 }}|{{ "home.choice_param" | trans_choice: 1, value: 9 }}|{{ "home.choice_param" | trans_choice: 2, value: 9 }}');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('No items|9 item|9 items');

    app()->setLocale('it');

    expect($template->render($this->environment->newRenderContext()))
        ->toBe('Nessun elemento|9 elemento|9 elementi');
});
