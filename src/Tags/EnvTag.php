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

    protected ?BodyNode $elseBody;

    public static function tagName(): string
    {
        return 'env';
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
        $this->environments = [];

        do {
            $env = $context->params->expression();

            $this->environments[] = match (true) {
                is_string($env) => $env,
                default => throw new SyntaxException('Invalid environment expression. It must be a string.')
            };

        } while ($context->params->consumeOrFalse(TokenType::Comma));

        $context->params->assertEnd();

        return $this;
    }

    public function render(RenderContext $context): string
    {
        if (app()->environment($this->environments)) {
            return $this->body->render($context);
        }

        return $this->elseBody?->render($context) ?? '';
    }
}
