<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Nodes\BodyNode;
use Keepsuit\Liquid\Parse\TagParseContext;
use Keepsuit\Liquid\Render\RenderContext;
use Keepsuit\Liquid\TagBlock;

class AuthTag extends TagBlock
{
    protected ?string $guard;

    protected BodyNode $body;

    public static function tagName(): string
    {
        return 'auth';
    }

    public function parse(TagParseContext $context): static
    {
        assert($context->body !== null);
        $this->body = $context->body;

        $this->guard = null;

        if ($context->params->isEnd()) {
            return $this;
        }

        $guard = $context->params->expression();
        $this->guard = match (true) {
            is_string($guard) => $guard,
            default => throw new SyntaxException('Invalid guard expression. It must be a string.')
        };

        $context->params->assertEnd();

        return $this;
    }

    public function render(RenderContext $context): string
    {
        if (auth()->guard($this->guard)->check()) {
            return $this->body->render($context);
        }

        return '';
    }
}
