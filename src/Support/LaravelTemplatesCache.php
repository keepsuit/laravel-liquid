<?php

namespace Keepsuit\LaravelLiquid\Support;

use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\Liquid\Support\TemplatesCache;
use Keepsuit\Liquid\Template;

class LaravelTemplatesCache extends TemplatesCache
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
            $this->set($name, $template);
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
        //        parent::remove($name);

        unset($this->cache[$name]);
    }
}
