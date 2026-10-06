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
        return $this->environment->parseTemplate($view);
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
