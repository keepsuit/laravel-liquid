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

it('shares loaded compiled templates between views and the facade until forgotten', function () {
    $files = mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
    app()->instance('files', $files);
    $sourcePath = $this->directory.'/views/main.liquid';
    $files->put($sourcePath, 'Hello {{ name }}');
    touch($sourcePath, time() - 10);
    $compiler = app('liquid.compiler');
    $compiledPath = $compiler->getCompiledPath($sourcePath);

    $template = Liquid::parse('main');
    expect($template)->toBeInstanceOf(CompiledTemplate::class);
    expect(view('main', ['name' => 'view'])->render())->toBe('Hello view');
    expect(Liquid::render('main', ['name' => 'facade'])->toHtml())->toBe('Hello facade');
    expect(Liquid::parse('main'))->toBe($template);

    app('view.engine.resolver')->resolve('liquid')->forgetCompiled();

    expect(file_exists($compiledPath))->toBeTrue();
    expect(Liquid::parse('main'))->not->toBe($template)->toBeInstanceOf(CompiledTemplate::class);
    expect(view('main', ['name' => 'again'])->render())->toBe('Hello again');
});

it('refreshes shared templates when sources change with or without view caching', function (bool $cacheViews) {
    config()->set('view.cache', $cacheViews);
    $sourcePath = $this->directory.'/views/main.liquid';
    app('files')->put($sourcePath, 'Hello {{ name }}');
    touch($sourcePath, time() - 10);

    expect(Liquid::parse('main'))->toBeInstanceOf(CompiledTemplate::class);
    expect(view('main', ['name' => 'world'])->render())->toBe('Hello world');

    app('files')->put($sourcePath, 'Updated {{ name }}');
    clearstatcache();

    expect(view('main', ['name' => 'view'])->render())->toBe('Updated view');
    expect(Liquid::render('main', ['name' => 'facade'])->toHtml())->toBe('Updated facade');
})->with([true, false]);

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

it('recovers corrupt artifacts and removes them', function () {
    app('files')->put($this->directory.'/views/main.liquid', 'Hello');
    expect(view('main')->render())->toBe('Hello');
    $compiler = app('liquid.compiler');
    $path = $compiler->getCompiledPath($this->directory.'/views/main.liquid');
    app('files')->put($path, '<?php return null;');
    expect(view('main')->render())->toBe('Hello');
    Liquid::environment()->templatesCache->remove('main');
    expect(file_exists($path))->toBeFalse();
});

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
    expect(Liquid::environment()->templatesCache->get('main'))->toBeNull();
    expect(view('main')->render())->toBe('Hello');
    expect(require $path)->toBeInstanceOf(CompiledTemplate::class);
});

it('compiles all liquid views with the liquid:cache command', function () {
    app('files')->makeDirectory($this->directory.'/views/partials', 0755, true);
    app('files')->put($this->directory.'/views/main.liquid', "{% render 'partials.item' %}");
    app('files')->put($this->directory.'/views/partials/item.liquid', 'Item');

    $this->artisan('liquid:cache')->assertSuccessful();

    $compiler = app('liquid.compiler');
    foreach (['main', 'partials.item'] as $name) {
        expect(file_exists($compiler->getCompiledPath($compiler->getPathFromTemplateName($name))))->toBeTrue();
    }
    expect(view('main')->render())->toBe('Item');
});

it('runs liquid:cache on optimize', function () {
    expect(\Illuminate\Support\ServiceProvider::$optimizeCommands)->toContain('liquid:cache');
});
