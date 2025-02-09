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
        parent::set($name, $template);

        $this->compiler->saveCompiledTemplate($template);
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
