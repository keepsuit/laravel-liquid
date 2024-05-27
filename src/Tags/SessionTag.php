<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Nodes\BodyNode;
use Keepsuit\Liquid\Parse\TagParseContext;
use Keepsuit\Liquid\Render\RenderContext;
use Keepsuit\Liquid\TagBlock;

class SessionTag extends TagBlock
{
    protected string $sessionKey;

    protected BodyNode $body;

    public static function tagName(): string
    {
        return 'session';
    }

    public function parse(TagParseContext $context): static
    {
        assert($context->body !== null);

        $key = $context->params->expression();
        $context->params->assertEnd();

        if (! is_string($key)) {
            throw new SyntaxException('Syntax error in "session" - Valid syntax: session "section-key"');
        }

        $this->sessionKey = $key;
        $this->body = $context->body;

        return $this;
    }

    public function render(RenderContext $context): string
    {
        if (! session()->has($this->sessionKey)) {
            return '';
        }

        $value = session()->get($this->sessionKey);

        return $context->stack(function () use ($context, $value) {
            $context->set('value', $value);

            return $this->body->render($context);
        });
    }
}
