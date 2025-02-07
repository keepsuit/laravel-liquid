<?php

namespace Keepsuit\LaravelLiquid\Support;

use Keepsuit\LaravelLiquid\LiquidCompiler;
use Keepsuit\Liquid\Contracts\LiquidFileSystem;

class LaravelLiquidFileSystem implements LiquidFileSystem
{
    public function __construct(
        protected LiquidCompiler $compiler,
    ) {}

    public function readTemplateFile(string $templateName): string
    {
        return $this->compiler->getFiles()->get($this->compiler->getPathFromTemplateName($templateName));
    }
}
