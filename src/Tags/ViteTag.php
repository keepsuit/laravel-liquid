<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Illuminate\Container\Container;
use Illuminate\Foundation\Vite;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Parse\TagParseContext;
use Keepsuit\Liquid\Parse\TokenType;
use Keepsuit\Liquid\Render\RenderContext;
use Keepsuit\Liquid\Tag;

class ViteTag extends Tag
{
    protected const SYNTAX_ERROR = 'Syntax Error in "vite" - Valid syntax: vite "entrypoint1" "entrypoint2", directory: "custom"';

    protected array $entrypoints;

    protected array $attributes;

    public static function tagName(): string
    {
        return 'vite';
    }

    public function parse(TagParseContext $context): static
    {
        $tokens = $context->params;

        $this->entrypoints = [];
        while ($entrypoint = $tokens->expression()) {
            if (! is_string($entrypoint)) {
                throw new SyntaxException(self::SYNTAX_ERROR);
            }

            $this->entrypoints[] = $entrypoint;

            $tokens->consumeOrFalse(TokenType::Comma);

            if ($tokens->look(TokenType::Colon, 1)) {
                break;
            }
        }

        if ($this->entrypoints === []) {
            throw new SyntaxException(self::SYNTAX_ERROR);
        }

        $this->attributes = [];
        while ($token = $tokens->consumeOrFalse(TokenType::Identifier)) {
            $tokens->consume(TokenType::Colon);
            $this->attributes[$token->data] = $tokens->expression();
        }

        $tokens->assertEnd();

        $context->getParseContext()->getOutputs()->push(
            'vite_entrypoints',
            ...array_map(fn (string $entrypoint) => ['entrypoint' => $entrypoint, ...$this->attributes], $this->entrypoints)
        );

        return $this;
    }

    public function render(RenderContext $context): string
    {
        $vite = Container::getInstance()->make(Vite::class);
        assert($vite instanceof Vite);

        $content = $vite($this->entrypoints, $this->attributes['directory'] ?? null)->toHtml();

        foreach ($vite->preloadedAssets() as $url => $attributes) {
            $context->getOutputs()->push('vite_preloads', ['href' => $url, 'attributes' => $attributes]);
        }

        return $content;
    }
}
