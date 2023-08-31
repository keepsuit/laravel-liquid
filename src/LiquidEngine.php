<?php

namespace Keepsuit\Liquid;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;

class LiquidEngine extends PhpEngine
{
    /**
     * A stack of the last compiled templates.
     */
    protected array $lastCompiled = [];

    /**
     * The view paths that were compiled or are not expired, keyed by the path.
     *
     * @var array<string, true>
     */
    protected array $compiledOrNotExpired = [];

    public function __construct(
        protected LiquidViewCompiler $compiler,
        Filesystem $files
    ) {
        parent::__construct($files);
    }

    public function getCompiler(): LiquidViewCompiler
    {
        return $this->compiler;
    }

    public function get($path, array $data = []): ?string
    {
        $this->lastCompiled[] = $path;

        // If this given view has expired, which means it has simply been edited since
        // it was last compiled, we will re-compile the views so we can evaluate a
        // fresh copy of the view. We'll pass the compiler the path of the view.
        if (! isset($this->compiledOrNotExpired[$path]) && $this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        $result = $this->compiler->render($path, $data);

        $this->compiledOrNotExpired[$path] = true;
        array_pop($this->lastCompiled);

        return $result;
    }

    public function forgetCompiled(): void
    {
        $this->compiledOrNotExpired = [];
    }
}
