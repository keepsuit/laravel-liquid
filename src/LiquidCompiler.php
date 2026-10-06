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
use Keepsuit\LaravelLiquid\Support\LaravelTemplatesCache;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\Exceptions\InternalException;
use Keepsuit\Liquid\Exceptions\LiquidException;
use Keepsuit\Liquid\Exceptions\SyntaxException;

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

    /**
     * @throws ViewException
     */
    public function render(string $path, array $data): string
    {
        try {
            $environment = $this->getEnvironment();
            $name = $this->getTemplateNameFromPath($path);
            $template = $environment->templatesCache instanceof LaravelTemplatesCache
                ? $environment->templatesCache->load($name)
                : $environment->parseTemplate($name);

            $context = $environment->newRenderContext(
                data: $data,
            );

            return $template->render($context);
        } catch (LiquidException $e) {
            $this->mapLiquidExceptionToLaravel($e, $path);
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

    public function getCachePath(): string
    {
        return $this->cachePath;
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

    public function forgetCompiled(): void
    {
        $cache = $this->getEnvironment()->templatesCache;

        if ($cache instanceof LaravelTemplatesCache) {
            $cache->forgetLoaded();
        }
    }
}
