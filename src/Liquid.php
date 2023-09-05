<?php

namespace Keepsuit\LaravelLiquid;

use Illuminate\Support\HtmlString;
use Keepsuit\Liquid\Template;
use Keepsuit\Liquid\TemplateFactory;

class Liquid
{
    public function __construct(
        protected TemplateFactory $factory
    ) {
    }

    public function parse(string $view): Template
    {
        return $this->factory->parseTemplate($view);
    }

    public function render(string $view, array $data = []): HtmlString
    {
        $content = $this->parse($view)
            ->render($this->factory->newRenderContext(environment: $data));

        return new HtmlString($content);
    }

    public function factory(): TemplateFactory
    {
        return $this->factory;
    }
}
