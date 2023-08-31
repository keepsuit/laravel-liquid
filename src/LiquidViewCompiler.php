<?php

namespace Keepsuit\Liquid;

use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;

class LiquidViewCompiler extends Compiler implements CompilerInterface
{
    protected ?TemplateFactory $factory = null;

    public function compile($path): void
    {
        if (! $this->cachePath) {
            return;
        }

        $source = $this->files->get($path);

        $template = $this->getTemplateFactory()->parse($source);

        $this->ensureCompiledDirectoryExists($this->getCompiledPath($path));

        $this->files->put($this->getCompiledPath($path), serialize($template));
    }

    public function render(string $path, array $data)
    {
        $template = unserialize($this->files->get($this->getCompiledPath($path)));

        if (! $template instanceof Template) {
            throw new \Exception('Template is not an instance of Template');
        }

        $context = $this->getTemplateFactory()->newRenderContext(
            environment: $data
        );

        return $template->render($context);
    }

    protected function getTemplateFactory(): TemplateFactory
    {
        if ($this->factory === null) {
            $this->factory = new TemplateFactory();
        }

        return $this->factory;
    }
}
