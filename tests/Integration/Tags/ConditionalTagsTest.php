<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Keepsuit\Liquid\Compiler\CompiledTemplate;
use Keepsuit\Liquid\ParsedTemplate;

beforeEach(function () {
    $this->directory = $this->temporaryDirectory()->path();
    app('files')->makeDirectory($this->directory.'/views', 0755, true);
    app('files')->put($this->directory.'/views/item.liquid', '[{{ item }}]');
    config()->set('view.paths', [$this->directory.'/views']);
    config()->set('view.compiled', $this->directory.'/cache');
    config()->set('view.cache', true);
    app()->forgetInstance('view');
    $this->environment = newLiquidEnvironment();
});

dataset('conditional tags', [
    'auth' => ['auth', ''],
    'guest' => ['guest', ''],
    'env' => ['env', '"production", "staging"'],
]);

it('renders nested loops, conditions and partials in both branches', function (string $mode, string $tag, string $params) {
    $body = '{% for item in items %}{% if item > 1 %}{% render "item", item: item %}{% endif %}{% endfor %}';
    $template = parseLiquidString($this->environment, "{% $tag $params %}yes:$body{% else %}no:$body{% end$tag %}", $mode);

    expect($template)->toBeInstanceOf($mode === 'parsed' ? ParsedTemplate::class : CompiledTemplate::class);

    foreach ([true, false, true] as $active) {
        if ($tag === 'env') {
            setEnv($active ? 'staging' : 'local');
        } elseif ($tag === 'guest' ? ! $active : $active) {
            Auth::setUser(new User);
        } else {
            Auth::guard()->forgetUser();
        }

        $expected = ($active ? 'yes:' : 'no:').'[2][3]';
        expect($template->render($this->environment->newRenderContext(data: ['items' => [1, 2, 3]])))->toBe($expected);
        expect(implode('', iterator_to_array($template->stream($this->environment->newRenderContext(data: ['items' => [1, 2, 3]])), false)))
            ->toBe($expected);
    }
})->with('liquid modes')->with('conditional tags');

it('renders tags of the same type nested in both branches', function (string $mode, string $tag, string $params) {
    $template = parseLiquidString($this->environment, "{% $tag $params %}outer:{% $tag $params %}inner{% else %}wrong{% end$tag %}{% else %}outer-else:{% $tag $params %}wrong{% else %}inner-else{% end$tag %}{% end$tag %}", $mode);

    foreach ([true, false] as $active) {
        if ($tag === 'env') {
            setEnv($active ? 'production' : 'local');
        } elseif ($tag === 'guest' ? ! $active : $active) {
            Auth::setUser(new User);
        } else {
            Auth::guard()->forgetUser();
        }

        $expected = $active ? 'outer:inner' : 'outer-else:inner-else';
        expect($template->render($this->environment->newRenderContext()))->toBe($expected);
        expect(implode('', iterator_to_array($template->stream($this->environment->newRenderContext()), false)))->toBe($expected);
    }
})->with('liquid modes')->with('conditional tags');
