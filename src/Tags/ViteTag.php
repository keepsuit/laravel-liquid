<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Illuminate\Container\Container;
use Illuminate\Foundation\Vite;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Parse\ParseContext;
use Keepsuit\Liquid\Parse\TagParseContext;
use Keepsuit\Liquid\Parse\TokenStream;
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
        $this->parseTokenStream($context->params);

        $this->pushEntrypointsToContext($context->getParseContext());

        return $this;
    }

    public function render(RenderContext $context): string
    {
        $originalVite = Container::getInstance()->make(Vite::class);
        assert($originalVite instanceof Vite);
        $vite = clone $originalVite;

        if ($directory = $this->attributes['directory'] ?? null) {
            $vite->useBuildDirectory($directory);
        }

        if ($hot = $this->attributes['hot'] ?? null) {
            $vite->useHotFile($hot);
        }

        $content = $vite->withEntryPoints($this->entrypoints)->toHtml();

        foreach ($vite->preloadedAssets() as $url => $attributes) {
            $context->getOutputs()->push('vite_preloads', array_filter([
                'href' => $url,
                'attributes' => $attributes,
                'directory' => $directory,
                'hot' => $hot,
            ]));
        }

        return $content;
    }

    protected function parseTokenStream(TokenStream $tokens): void
    {
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
    }

    protected function pushEntrypointsToContext(ParseContext $parseContext): void
    {
        $parseContext->getOutputs()->push(
            'vite_entrypoints',
            ...array_map(fn (string $entrypoint) => [
                'entrypoint' => $entrypoint,
                ...$this->attributes,
            ], $this->entrypoints)
        );
    }
}
