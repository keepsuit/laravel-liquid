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

    protected ?BodyNode $elseBody;

    public static function tagName(): string
    {
        return 'session';
    }

    public function isSubTag(string $tagName): bool
    {
        return $tagName === 'else';
    }

    public function parse(TagParseContext $context): static
    {
        assert($context->body !== null);

        if ($context->tag === 'else') {
            $this->elseBody = $context->body;

            return $this;
        }

        $this->body = $context->body;
        $this->elseBody = null;

        $key = $context->params->expression();
        $context->params->assertEnd();

        if (! is_string($key)) {
            throw new SyntaxException('Syntax error in "session" - Valid syntax: session "section-key"');
        }

        $this->sessionKey = $key;

        return $this;
    }

    public function render(RenderContext $context): string
    {
        if (! session()->has($this->sessionKey)) {
            return $this->elseBody?->render($context) ?? '';
        }

        $value = session()->get($this->sessionKey);

        return $context->stack(function () use ($context, $value) {
            $context->set('value', $value);

            return $this->body->render($context);
        });
    }
}
