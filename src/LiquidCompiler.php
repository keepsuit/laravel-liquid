<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\ViewException;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
use Keepsuit\Liquid\Exceptions\InternalException;
use Keepsuit\Liquid\Exceptions\LiquidException;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Template;
use Keepsuit\Liquid\TemplateFactory;

class LiquidCompiler extends Compiler implements CompilerInterface
{
    protected ?TemplateFactory $factory = null;

    public function compile($path): void
    {
        if (! $this->cachePath) {
            return;
        }

        try {
            $template = $this->getTemplateFactory()->parseTemplate($this->getTemplateNameFromPath($path));
        } catch (LiquidException $e) {
            $this->mapLiquidExceptionToLaravel($e, $path);
        }

        $this->ensureCompiledDirectoryExists($this->getCompiledPath($path));

        $this->files->put($this->getCompiledPath($path), serialize($template));
    }

    /**
     * @throws ViewException
     * @throws FileNotFoundException
     */
    public function render(string $path, array $data): string
    {
        $template = unserialize($this->files->get($this->getCompiledPath($path)));

        if (! $template instanceof Template) {
            throw new \Exception('Template is not an instance of Template');
        }

        try {
            $context = $this->getTemplateFactory()->newRenderContext(
                environment: $data,
                rethrowExceptions: true,
            );

            return $template->render($context);
        } catch (LiquidException $e) {
            $this->mapLiquidExceptionToLaravel($e, $path);
        }
    }

    protected function getTemplateNameFromPath(string $path): string
    {
        return Container::getInstance()->make(LaravelLiquidFileSystem::class)->getTemplateNameFromPath($path);
    }

    protected function getTemplateFactory(): TemplateFactory
    {
        if ($this->factory === null) {
            $this->factory = Container::getInstance()->make(TemplateFactory::class);
        }

        return $this->factory;
    }

    /**
     * @return never-return
     *
     * @throws ViewException
     */
    protected function mapLiquidExceptionToLaravel(LiquidException $e, string $path): void
    {
        throw new ViewException(
            message: sprintf('%s (View: %s)', $e->getMessage(), $path),
            previous: match (true) {
                $e instanceof SyntaxException => new SyntaxException(
                    message: $e->getMessage(),
                    filename: $path,
                    line: $e->lineNumber,
                ),
                $e instanceof InternalException => $e->getPrevious(),
                default => $e,
            },
        );
    }
}
