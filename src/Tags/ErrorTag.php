<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Illuminate\Support\ViewErrorBag;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Nodes\BodyNode;
use Keepsuit\Liquid\Nodes\VariableLookup;
use Keepsuit\Liquid\Parse\TagParseContext;
use Keepsuit\Liquid\Parse\TokenType;
use Keepsuit\Liquid\Render\RenderContext;
use Keepsuit\Liquid\TagBlock;

class ErrorTag extends TagBlock
{
    protected string $errorKey;

    protected BodyNode $body;

    protected string|VariableLookup $errorBag = 'default';

    public static function tagName(): string
    {
        return 'error';
    }

    public function parse(TagParseContext $context): static
    {
        assert($context->body !== null);

        $key = $context->params->expression();

        if (! is_string($key)) {
            throw new SyntaxException('Syntax error in "error" - Valid syntax: error "validation-key"');
        }

        while ($context->params->look(TokenType::Comma) || $context->params->look(TokenType::Identifier)) {
            $context->params->consumeOrFalse(TokenType::Comma);

            $param = $context->params->consume(TokenType::Identifier);

            if ($param->data === 'bag') {
                $context->params->consume(TokenType::Colon);
                $bag = $context->params->expression();
                $this->errorBag = match (true) {
                    is_string($bag) || $bag instanceof VariableLookup => $bag,
                    default => throw new SyntaxException('Invalid value for param "bag", only string or variable are allowed.'),
                };

                continue;
            }

            throw new SyntaxException("Invalid param for tag 'error', only 'bag' is allowed.");
        }

        $context->params->assertEnd();

        $this->errorKey = $key;
        $this->body = $context->body;

        return $this;
    }

    public function render(RenderContext $context): string
    {
        $errorBag = session()->get('errors') ?? new ViewErrorBag();
        assert($errorBag instanceof ViewErrorBag);

        $bagKey = match (true) {
            $this->errorBag instanceof VariableLookup => $this->errorBag->evaluate($context),
            default => $this->errorBag,
        };

        if (! is_string($bagKey)) {
            throw new SyntaxException('Invalid value for param "bag", only string or variable are allowed.');
        }

        $messageBag = $errorBag->getBag($bagKey);

        if (! $messageBag->has($this->errorKey)) {
            return '';
        }

        $message = $messageBag->first($this->errorKey);

        return $context->stack(function () use ($message, $context) {
            $context->set('message', $message);

            return $this->body->render($context);
        });
    }
}
