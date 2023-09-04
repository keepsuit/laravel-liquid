<?php

namespace Keepsuit\LaravelLiquid\Tags;

use Illuminate\Container\Container;
use Illuminate\Foundation\Vite;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Parse\ParseContext;
use Keepsuit\Liquid\Parse\Parser;
use Keepsuit\Liquid\Parse\Regex;
use Keepsuit\Liquid\Parse\Tokenizer;
use Keepsuit\Liquid\Parse\TokenType;
use Keepsuit\Liquid\Render\Context;
use Keepsuit\Liquid\Tag;

class ViteTag extends Tag
{
    protected const FullSyntax = '/((?:(?:'.Regex::QuotedString.')\s*)+)(?:,\s*directory:\s*('.Regex::QuotedString.'))?/';

    protected const EntryPointsSyntax = '/('.Regex::QuotedString.')/';

    protected array $entrypoints;

    protected array $attributes;

    public static function tagName(): string
    {
        return 'vite';
    }

    public function parse(ParseContext $parseContext, Tokenizer $tokenizer): static
    {
        $parser = new Parser($this->markup);

        $entrypoints = [];
        while ($token = $parser->consumeOrFalse(TokenType::String)) {
            $entrypoints[] = $token;
            $parser->consumeOrFalse(TokenType::Comma);
            if ($parser->look(TokenType::Colon, 2)) {
                break;
            }
        }

        if ($entrypoints === []) {
            throw new SyntaxException('Syntax Error in "vite" - Valid syntax: vite "entrypoint1" "entrypoint2", directory: "custom"');
        }

        $this->entrypoints = array_map(
            fn (string $expression) => $this->parseExpression($parseContext, $expression),
            $entrypoints,
        );

        $this->attributes = array_map(
            fn (string $expression) => $this->parseExpression($parseContext, $expression),
            $parser->attributes(TokenType::Comma)
        );

        $parser->consume(TokenType::EndOfString);

        return $this;
    }

    public function render(Context $context): string
    {
        $vite = Container::getInstance()->make(Vite::class);
        assert($vite instanceof Vite);

        return $vite($this->entrypoints, $this->attributes['directory'] ?? null)->toHtml();
    }
}
