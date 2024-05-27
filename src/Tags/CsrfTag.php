<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Keepsuit\Liquid\Parse\TagParseContext;
use Keepsuit\Liquid\Render\RenderContext;
use Keepsuit\Liquid\Tag;

class CsrfTag extends Tag
{
    public static function tagName(): string
    {
        return 'csrf';
    }

    public function parse(TagParseContext $context): static
    {
        $context->params->assertEnd();

        return $this;
    }

    public function render(RenderContext $context): string
    {
        return csrf_field();
    }
}
