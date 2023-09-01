<?php

namespace Keepsuit\Liquid;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewException;
use Keepsuit\Liquid\Contracts\LiquidFileSystem;
use Keepsuit\Liquid\Exceptions\InternalException;
use Keepsuit\Liquid\Exceptions\LiquidException;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Tags\ViteTag;

class LiquidCompiler extends Compiler implements CompilerInterface, LiquidFileSystem
{
    protected ?TemplateFactory $factory = null;

    protected ?FileViewFinder $viewFinder = null;

    public function readTemplateFile(string $templateName): string
    {
        $path = $this->getViewFinder()->find($templateName);

        return $this->files->get($path);
    }

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
        $templateName = Collection::make($this->getViewFinder()->getViews())
            ->mapWithKeys(fn (string $templatePath, string $templateName) => [$templatePath => $templateName])
            ->get($path);

        if ($templateName === null) {
            throw new \RuntimeException('Template not found from path: '.$path);
        }

        return $templateName;
    }

    protected function getTemplateFactory(): TemplateFactory
    {
        if ($this->factory === null) {
            $this->factory = TemplateFactory::new()
                ->setFilesystem($this)
                ->lineNumbers((bool) config('app.debug', false))
                ->registerTag(ViteTag::class);
        }

        return $this->factory;
    }

    protected function getViewFinder(): FileViewFinder
    {
        if ($this->viewFinder === null) {
            $viewFinder = Container::getInstance()
                ->make(Factory::class)
                ->getFinder();
            assert($viewFinder instanceof FileViewFinder);
            $this->viewFinder = $viewFinder;
        }

        return $this->viewFinder;
    }

    /**
     * @throws ViewException
     * @return never-return
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
