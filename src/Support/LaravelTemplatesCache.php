<?php

namespace Keepsuit\LaravelLiquid\Support;

use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\Liquid\Template;
use Keepsuit\Liquid\TemplatesCache\CompiledTemplatesCache;

class LaravelTemplatesCache extends CompiledTemplatesCache
{
    public function __construct(
        protected LiquidCompiler $compiler,
    ) {
        parent::__construct($compiler->getCachePath());
    }

    public function get(string $name): ?Template
    {
        if ($this->compiler->isExpired($this->compiler->getPathFromTemplateName($name))) {
            unset($this->cache[$name]);

            return null;
        }

        return parent::get($name);
    }

    public function forgetLoaded(): void
    {
        $this->cache = [];
    }

    public function remove(string $name): void
    {
        unset($this->cache[$name]);

        $this->compiler->getFiles()->delete($this->getCompiledPath($name));
    }

    protected function getCompiledPath(string $name): string
    {
        return $this->compiler->getCompiledPath($this->compiler->getPathFromTemplateName($name));
    }
}
