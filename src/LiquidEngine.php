<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;

class LiquidEngine extends PhpEngine
{
    public function __construct(
        protected LiquidCompiler $compiler,
        Filesystem $files
    ) {
        parent::__construct($files);
    }

    public function get($path, array $data = []): ?string
    {
        return $this->compiler->render($path, $data);
    }

    public function forgetCompiled(): void
    {
        $this->compiler->forgetCompiled();
    }
}
