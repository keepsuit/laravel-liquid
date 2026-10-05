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
use Keepsuit\Liquid\Compiler\CompiledTemplate;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\Exceptions\InternalException;
use Keepsuit\Liquid\Exceptions\LiquidException;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\ParsedTemplate;
use Keepsuit\Liquid\Template;
use Keepsuit\Liquid\TemplateSharedState;

class LiquidCompiler extends Compiler implements CompilerInterface
{
    public function compile($path): void
    {
        if (! $this->cachePath) {
            return;
        }

        try {
            $this->getEnvironment()->newParseContext()->parseTemplate(
                $this->getTemplateNameFromPath($path),
                force: true,
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

        if (! $template instanceof ParsedTemplate) {
            throw new \InvalidArgumentException('PHP compilation requires a parsed template.');
        }

        $this->getEnvironment()->compile($template, $compiledPath);

        // Liquid's PHP artifact omits parse-time partials and outputs; cache only that metadata separately.
        $this->files->replace($compiledPath.'.state', serialize($template->getState()));
    }

    public function removeCompiledTemplate(string $templateName): void
    {
        $compiledPath = $this->getCompiledPath($this->getPathFromTemplateName($templateName));

        $this->files->delete([$compiledPath, $compiledPath.'.state']);
    }

    public function clearCompiledTemplates(): void
    {
        $this->files->deleteDirectory($this->cachePath);
        $this->ensureCompiledDirectoryExists($this->cachePath);
    }

    /**
     * @throws ViewException
     */
    public function render(string $path, array $data): string
    {
        $template = $this->resolveCompiledTemplateByPath($path);

        if ($template === null) {
            $this->compile($path);
            $template = $this->resolveCompiledTemplateByPath($path);
        }

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
        $compiledPath = $this->getCompiledPath($path);

        if (! $this->files->exists($compiledPath)) {
            return null;
        }

        try {
            $compiled = require $compiledPath;

            if (! $compiled instanceof CompiledTemplate || ! $this->files->exists($compiledPath.'.state')) {
                return null;
            }

            $state = @unserialize($this->files->get($compiledPath.'.state'));

            if (! $state instanceof TemplateSharedState) {
                return null;
            }

            $compiled->getState()->partials = $state->partials;
            $compiled->getState()->outputs->merge($state->outputs);

            return $compiled;
        } catch (\Throwable) {
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
        foreach ($template->getState()->partials as $partial) {
            $path = $this->getPathFromTemplateName($partial);
            if ($this->isExpired($path)) {
                $this->compile($path);
            }
        }
    }
}
