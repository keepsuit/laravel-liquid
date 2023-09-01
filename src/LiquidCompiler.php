<?php

namespace Keepsuit\Liquid;

use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\ViewException;
use Keepsuit\Liquid\Exceptions\SyntaxException;

class LiquidCompiler extends Compiler implements CompilerInterface
{
    protected ?TemplateFactory $factory = null;

    public function compile($path): void
    {
        if (! $this->cachePath) {
            return;
        }

        $source = $this->files->get($path);

        try {
            $template = $this->getTemplateFactory()->parse(
                source: $source,
                lineNumbers: (bool) config('app.debug', false)
            );
        } catch (Exceptions\SyntaxException $e) {
            throw new ViewException(
                message: sprintf('%s (View: %s)', $e->getMessage(), $path),
                previous: new SyntaxException(
                    message: $e->getMessage(),
                    line: $e->lineNumber,
                    filename: $path,
                ),
            );
        }

        $this->ensureCompiledDirectoryExists($this->getCompiledPath($path));

        $this->files->put($this->getCompiledPath($path), serialize($template));
    }

    public function render(string $path, array $data): string
    {
        $template = unserialize($this->files->get($this->getCompiledPath($path)));

        if (! $template instanceof Template) {
            throw new \Exception('Template is not an instance of Template');
        }

        $context = $this->getTemplateFactory()->newRenderContext(
            environment: $data,
            rethrowExceptions: true,
        );

        return $template->render($context);
    }

    protected function getTemplateFactory(): TemplateFactory
    {
        if ($this->factory === null) {
            $this->factory = TemplateFactory::new();
        }

        return $this->factory;
    }
}
