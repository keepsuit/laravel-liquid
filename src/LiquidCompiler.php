<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewException;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\Exceptions\InternalException;
use Keepsuit\Liquid\Exceptions\LiquidException;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Template;

class LiquidCompiler extends Compiler implements CompilerInterface
{
    public function compile($path): void
    {
        if (! $this->cachePath) {
            return;
        }

        try {
            $this->getEnvironment()->parseTemplate(
                $this->getTemplateNameFromPath($path),
            );
        } catch (LiquidException $e) {
            $this->mapLiquidExceptionToLaravel($e, $path);
        }
    }

    public function saveCompiledTemplate(Template $template): void
    {
        if ($template->name() === null) {
            return;
        }

        $path = $this->getPathFromTemplateName($template->name());

        $compiledPath = $this->getCompiledPath($path);
        $this->ensureCompiledDirectoryExists($compiledPath);
        $this->files->put($compiledPath, serialize($template));
    }

    /**
     * @throws ViewException
     */
    public function render(string $path, array $data): string
    {
        $template = $this->resolveCompiledTemplateByPath($path);

        if (! $template instanceof Template) {
            throw new \Exception('Template is not an instance of Template');
        }

        $this->ensureTemplatePartialsAreCompiled($template);

        try {
            $context = $this->getEnvironment()->newRenderContext(
                data: $data,
            );

            return $template->render($context);
        } catch (LiquidException $e) {
            $this->mapLiquidExceptionToLaravel($e, $path);
        }
    }

    public function resolveCompiledTemplateByPath(string $path): ?Template
    {
        try {
            $compiled = unserialize($this->files->get($this->getCompiledPath($path)));

            if (! $compiled instanceof Template) {
                return null;
            }

            return $compiled;
        } catch (FileNotFoundException $e) {
            return null;
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function getTemplateNameFromPath(string $path): string
    {
        $templateName = Collection::make($this->getViewFinder()->getViews())
            ->mapWithKeys(fn (string $templatePath, string $templateName) => [$templatePath => $templateName])
            ->get($path);

        if ($templateName === null) {
            throw new FileNotFoundException('Template not found from path: '.$path);
        }

        return $templateName;
    }

    public function getPathFromTemplateName(string $templateName): string
    {
        return $this->getViewFinder()->find($templateName);
    }

    protected function getEnvironment(): Environment
    {
        return Container::getInstance()->make('liquid.environment');
    }

    public function getViewFinder(): FileViewFinder
    {
        $viewFinder = Container::getInstance()->make(Factory::class)->getFinder();

        assert($viewFinder instanceof FileViewFinder, 'ViewFinder must be an instance of FileViewFinder');

        return $viewFinder;
    }

    public function getFiles(): Filesystem
    {
        return $this->files;
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

    protected function ensureTemplatePartialsAreCompiled(Template $template): void
    {
        foreach ($template->state->partials as $partial) {
            $path = $this->getPathFromTemplateName($partial);
            if (! $this->files->exists($this->getCompiledPath($path))) {
                $this->compile($path);
            }
        }
    }
}
