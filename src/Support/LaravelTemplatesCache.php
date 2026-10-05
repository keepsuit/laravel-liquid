<?php

namespace Keepsuit\LaravelLiquid\Support;

use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\Liquid\Template;
use Keepsuit\Liquid\TemplatesCache\MemoryTemplatesCache;

class LaravelTemplatesCache extends MemoryTemplatesCache
{
    public function __construct(
        protected LiquidCompiler $compiler,
    ) {}

    public function get(string $name): ?Template
    {
        $path = $this->compiler->getPathFromTemplateName($name);

        if ($this->compiler->isExpired($path)) {
            unset($this->cache[$name]);

            return null;
        }

        if ($template = parent::get($name)) {
            return $template;
        }

        $template = $this->compiler->resolveCompiledTemplateByPath($path);

        if ($template !== null) {
            parent::set($name, $template);
        }

        return $template;
    }

    public function set(string $name, Template $template): void
    {
        $this->compiler->saveCompiledTemplate($template);

        unset($this->cache[$name]);
    }

    public function load(string $name): Template
    {
        $template = $this->get($name);

        if ($template === null) {
            $path = $this->compiler->getPathFromTemplateName($name);
            $this->compiler->compile($path);
            $template = $this->compiler->resolveCompiledTemplateByPath($path);

            if ($template === null) {
                throw new \RuntimeException('Unable to load compiled Liquid template: '.$name);
            }

            parent::set($name, $template);
        }

        foreach ($template->getState()->partials as $partial) {
            $this->load($partial);
        }

        return $template;
    }

    public function forgetLoaded(): void
    {
        parent::clear();
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function remove(string $name): void
    {
        unset($this->cache[$name]);

        $this->compiler->removeCompiledTemplate($name);
    }

    public function clear(): void
    {
        parent::clear();

        $this->compiler->clearCompiledTemplates();
    }
}
