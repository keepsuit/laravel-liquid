<?php

namespace Keepsuit\LaravelLiquid\Support;

use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\Liquid\Template;
use Keepsuit\Liquid\TemplatesCache\CompiledTemplatesCache;

class LaravelTemplatesCache extends CompiledTemplatesCache
{
    /** @var array<string, true> */
    protected array $checked = [];

    public function __construct(
        protected LiquidCompiler $compiler,
    ) {
        parent::__construct($compiler->getCachePath());
    }

    public function get(string $name): ?Template
    {
        if (! isset($this->checked[$name])) {
            if ($this->compiler->isExpired($this->compiler->getPathFromTemplateName($name))) {
                unset($this->cache[$name]);

                return null;
            }

            $this->checked[$name] = true;
        }

        return parent::get($name);
    }

    public function set(string $name, Template $template): void
    {
        $this->checked[$name] = true;

        parent::set($name, $template);
    }

    public function forgetLoaded(): void
    {
        $this->cache = [];
        $this->checked = [];
    }

    public function remove(string $name): void
    {
        unset($this->cache[$name], $this->checked[$name]);

        $this->compiler->getFiles()->delete($this->getCompiledPath($name));
    }

    protected function getCompiledPath(string $name): string
    {
        return $this->compiler->getCompiledPath($this->compiler->getPathFromTemplateName($name));
    }
}
