<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Keepsuit\Liquid\Render\RenderContext;

class GuestTag extends AuthTag
{
    public static function tagName(): string
    {
        return 'guest';
    }

    public function render(RenderContext $context): string
    {
        if (auth()->guard($this->guard)->guest()) {
            return $this->body->render($context);
        }

        return $this->elseBody?->render($context) ?? '';
    }
}
