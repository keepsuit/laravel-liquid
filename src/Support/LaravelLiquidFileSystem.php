<?php

namespace Keepsuit\LaravelLiquid\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewFinderInterface;
use Keepsuit\Liquid\Contracts\LiquidFileSystem;

class LaravelLiquidFileSystem implements LiquidFileSystem
{
    public function __construct(
        protected Filesystem $files,
        protected ViewFinderInterface $viewFinder
    ) {}

    public function readTemplateFile(string $templateName): string
    {
        return $this->files->get($this->getPathFromTemplateName($templateName));
    }

    public function getTemplateNameFromPath(string $path): string
    {
        if (! $this->viewFinder instanceof FileViewFinder) {
            throw new \RuntimeException('ViewFinder must be an instance of FileViewFinder');
        }

        $templateName = Collection::make($this->viewFinder->getViews())
            ->mapWithKeys(fn (string $templatePath, string $templateName) => [$templatePath => $templateName])
            ->get($path);

        if ($templateName === null) {
            throw new \RuntimeException('Template not found from path: '.$path);
        }

        return $templateName;
    }

    public function getPathFromTemplateName(string $templateName): string
    {
        return $this->viewFinder->find($templateName);
    }
}
