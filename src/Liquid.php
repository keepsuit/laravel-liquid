<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Support\HtmlString;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\Template;

class Liquid
{
    public function __construct(
        protected Environment $environment
    ) {}

    public function parse(string $view): Template
    {
        $template = $this->environment->parseTemplate($view);

        return $this->environment->templatesCache->get($view) ?? $template;
    }

    public function render(string $view, array $data = []): HtmlString
    {
        $content = $this->parse($view)
            ->render($this->environment->newRenderContext(data: $data));

        return new HtmlString($content);
    }

    /**
     * @return \Generator<string>
     */
    public function stream(string $view, array $data = []): \Generator
    {
        yield from $this->parse($view)
            ->stream($this->environment->newRenderContext(data: $data));
    }

    public function environment(): Environment
    {
        return $this->environment;
    }
}
