<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Keepsuit\Liquid\Nodes\BodyNode;
use Keepsuit\Liquid\Render\RenderContext;

class GuestTag extends AuthTag
{
    protected ?string $guard;

    protected BodyNode $body;

    public static function tagName(): string
    {
        return 'guest';
    }

    public function render(RenderContext $context): string
    {
        if (auth()->guard($this->guard)->guest()) {
            return $this->body->render($context);
        }

        return '';
    }
}
