<?php

use Keepsuit\LaravelLiquid\Facades\Liquid;
use Keepsuit\Liquid\Compiler\CompiledTemplate;

beforeEach(function () {
    $this->directory = realpath(sys_get_temp_dir()).'/laravel-liquid-'.bin2hex(random_bytes(8));
    app('files')->makeDirectory($this->directory.'/views', 0755, true);
    config()->set('view.paths', [$this->directory.'/views']);
    config()->set('view.compiled', $this->directory.'/cache');
    config()->set('view.cache', true);
    config()->set('app.debug', true);
    app()->forgetInstance('view');
});

afterEach(function () {
    app('files')->deleteDirectory($this->directory);
});

it('loads native views and partials from disk and recompiles changed partials', function () {
    $files = app('files');
    $files->put($this->directory.'/views/main.liquid', "{% render 'partial', name: name %}");
    $files->put($this->directory.'/views/partial.liquid', 'Hello {{ name }}');
    touch($this->directory.'/views/main.liquid', time() - 10);
    touch($this->directory.'/views/partial.liquid', time() - 10);

    expect(view('main', ['name' => 'world'])->render())->toBe('Hello world');
    $compiler = app('liquid.compiler');
    $path = $compiler->getCompiledPath($this->directory.'/views/main.liquid');
    expect(require $path)->toBeInstanceOf(CompiledTemplate::class);

    app()->forgetInstance('liquid.environment');
    app()->forgetInstance('liquid.factory');
    Liquid::clearResolvedInstances();
    expect(Liquid::parse('main'))->toBeInstanceOf(CompiledTemplate::class);
    expect(view('main', ['name' => 'again'])->render())->toBe('Hello again');

    $partialPath = $compiler->getCompiledPath($this->directory.'/views/partial.liquid');
    $files->put($this->directory.'/views/partial.liquid', 'Updated {{ name }}');
    touch($this->directory.'/views/partial.liquid', filemtime($partialPath) + 1);
    clearstatcache();

    expect(view('main', ['name' => 'world'])->render())->toBe('Updated world');
    $files->delete($partialPath);
    expect(view('main', ['name' => 'world'])->render())->toBe('Updated world');
});

it('retains parse outputs and renders custom tags and filters after disk reload', function () {
    app('files')->put($this->directory.'/views/main.liquid', <<<'LIQUID'
    {% vite 'resources/js/app.js' %}{% session 'status' %}{{ value }}{% else %}missing{% endsession %} {{ '/home' | url }}
    LIQUID);
    touch($this->directory.'/views/main.liquid', time() - 10);
    makeViteManifest();

    try {
        Liquid::parse('main');
        app()->forgetInstance('liquid.environment');
        app()->forgetInstance('liquid.factory');
        Liquid::clearResolvedInstances();
        $template = Liquid::parse('main');
        expect($template)->toBeInstanceOf(CompiledTemplate::class);
        expect($template->getState()->outputs->get('vite_entrypoints'))->toHaveCount(1);
        expect(Liquid::render('main')->toHtml())->toContain('missing', url('/home'), 'app.versioned.js');
        session()->put('status', 'ready');
        expect(Liquid::render('main')->toHtml())->toContain('ready');
        expect(implode('', iterator_to_array($template->stream(Liquid::environment()->newRenderContext()))))
            ->toBe(Liquid::render('main')->toHtml());
    } finally {
        cleanViteManifest();
    }
});

it('recovers corrupt artifacts and removes artifacts with metadata', function () {
    app('files')->put($this->directory.'/views/main.liquid', 'Hello');
    expect(view('main')->render())->toBe('Hello');
    $compiler = app('liquid.compiler');
    $path = $compiler->getCompiledPath($this->directory.'/views/main.liquid');
    app('files')->put($path, '<?php return null;');
    expect(view('main')->render())->toBe('Hello');
    $compiler->removeCompiledTemplate('main');
    expect(file_exists($path))->toBeFalse();
    expect(file_exists($path.'.state'))->toBeFalse();
});

it('recompiles views when metadata is missing or corrupt', function (bool $missing) {
    app('files')->put($this->directory.'/views/main.liquid', 'Hello');
    expect(view('main')->render())->toBe('Hello');
    $compiler = app('liquid.compiler');
    $sourcePath = $compiler->getPathFromTemplateName('main');
    $path = $compiler->getCompiledPath($sourcePath);

    if ($missing) {
        app('files')->delete($path.'.state');
    } else {
        app('files')->put($path.'.state', 'invalid metadata');
    }

    expect($compiler->resolveCompiledTemplateByPath($sourcePath))->toBeNull();
    expect(view('main')->render())->toBe('Hello');
    expect($compiler->resolveCompiledTemplateByPath($sourcePath))->toBeInstanceOf(CompiledTemplate::class);
})->with([true, false]);

it('maps compilation and rendering errors to Laravel view exceptions', function (string $source) {
    config()->set('liquid.strict_variables', true);
    app('files')->put($this->directory.'/views/main.liquid', $source);

    expect(fn () => view('main')->render())->toThrow(\Illuminate\View\ViewException::class);
})->with([
    'syntax error' => '{% if %}',
    'render error' => '{{ missing }}',
]);

it('clears all native artifacts and their metadata', function () {
    app('files')->put($this->directory.'/views/main.liquid', 'Hello');
    view('main')->render();
    app('liquid.environment')->templatesCache->clear();

    expect(glob($this->directory.'/cache/*'))->toBe([]);
    expect(view('main')->render())->toBe('Hello');
});

it('replaces legacy exported templates with native artifacts', function () {
    app('files')->put($this->directory.'/views/main.liquid', 'Hello');
    $compiler = app('liquid.compiler');
    $sourcePath = $compiler->getPathFromTemplateName('main');
    $path = $compiler->getCompiledPath($sourcePath);
    app('files')->makeDirectory(dirname($path), 0755, true);
    app('files')->put($path, '<?php return new \Keepsuit\Liquid\ParsedTemplate(new \Keepsuit\Liquid\Nodes\Document(new \Keepsuit\Liquid\Nodes\BodyNode));');

    expect(require $path)->toBeInstanceOf(\Keepsuit\Liquid\ParsedTemplate::class);
    expect($compiler->resolveCompiledTemplateByPath($sourcePath))->toBeNull();
    expect(view('main')->render())->toBe('Hello');
    expect(require $path)->toBeInstanceOf(CompiledTemplate::class);
});
