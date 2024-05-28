<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Nodes\BodyNode;
use Keepsuit\Liquid\Parse\TagParseContext;
use Keepsuit\Liquid\Parse\TokenType;
use Keepsuit\Liquid\Render\RenderContext;
use Keepsuit\Liquid\TagBlock;

class EnvTag extends TagBlock
{
    /**
     * @var string[]
     */
    protected array $environments;

    protected BodyNode $body;

    public static function tagName(): string
    {
        return 'env';
    }

    public function parse(TagParseContext $context): static
    {
        $this->environments = [];

        do {
            $env = $context->params->expression();

            $this->environments[] = match (true) {
                is_string($env) => $env,
                default => throw new SyntaxException('Invalid environment expression. It must be a string.')
            };

        } while ($context->params->consumeOrFalse(TokenType::Comma));

        $context->params->assertEnd();

        assert($context->body !== null);
        $this->body = $context->body;

        return $this;
    }

    public function render(RenderContext $context): string
    {
        if (app()->environment($this->environments)) {
            return $this->body->render($context);
        }

        return '';
    }
}
