<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Support\HtmlString;
use Keepsuit\LaravelLiquid\Support\LaravelTemplatesCache;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\Template;

class Liquid
{
    public function __construct(
        protected Environment $environment
    ) {}

    public function parse(string $view): Template
    {
        if ($this->environment->templatesCache instanceof LaravelTemplatesCache) {
            return $this->environment->templatesCache->load($view);
        }

        return $this->environment->parseTemplate($view);
    }

    public function render(string $view, array $data = []): HtmlString
    {
        $content = $this->parse($view)
            ->render($this->environment->newRenderContext(data: $data));

        return new HtmlString($content);
    }

    public function environment(): Environment
    {
        return $this->environment;
    }
}
