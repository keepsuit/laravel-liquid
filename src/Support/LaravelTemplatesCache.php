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

    public function load(string $name): Template
    {
        $template = $this->get($name);

        if ($template === null) {
            $this->compiler->compile($this->compiler->getPathFromTemplateName($name));
            $template = parent::get($name);

            if ($template === null) {
                throw new \RuntimeException('Unable to load compiled Liquid template: '.$name);
            }
        }

        foreach ($template->getState()->partials as $partial) {
            $this->load($partial);
        }

        return $template;
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
