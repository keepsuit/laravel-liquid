<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\ViewException;
use Keepsuit\LaravelLiquid\Support\LaravelLiquidFileSystem;
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
            $template = $this->getEnvironment()->parseTemplate(
                $this->getTemplateNameFromPath($path),
            );
        } catch (LiquidException $e) {
            $this->mapLiquidExceptionToLaravel($e, $path);
        }

        $this->saveCompiledTemplate($template);
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
        $template = $this->resolveTemplateByPath($path);

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

    public function resolveTemplateByName(string $name): ?Template
    {
        $path = $this->getPathFromTemplateName($name);

        return $this->resolveTemplateByPath($path);
    }

    public function resolveTemplateByPath(string $path): ?Template
    {
        try {
            $compiled = unserialize($this->files->get($this->getCompiledPath($path)));

            if (! $compiled instanceof Template) {
                throw new \RuntimeException('Invalid compiled template');
            }

            return $compiled;
        } catch (FileNotFoundException $e) {
            return null;
        }
    }

    protected function getTemplateNameFromPath(string $path): string
    {
        return $this->getFilesystem()->getTemplateNameFromPath($path);
    }

    protected function getPathFromTemplateName(string $templateName): string
    {
        return $this->getFilesystem()->getPathFromTemplateName($templateName);
    }

    protected function getFilesystem(): LaravelLiquidFileSystem
    {
        return Container::getInstance()->make(LaravelLiquidFileSystem::class);
    }

    protected function getEnvironment(): Environment
    {
        return Container::getInstance()->make('liquid.environment');
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
