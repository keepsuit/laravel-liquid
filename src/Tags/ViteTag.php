<?php

namespace Keepsuit\Liquid\Tags;

use Illuminate\Container\Container;
use Illuminate\Foundation\Vite;
use Keepsuit\Liquid\Exceptions\SyntaxException;
use Keepsuit\Liquid\Parse\ParseContext;
use Keepsuit\Liquid\Parse\Regex;
use Keepsuit\Liquid\Parse\Tokenizer;
use Keepsuit\Liquid\Render\Context;
use Keepsuit\Liquid\Tag;

class ViteTag extends Tag
{
    protected const FullSyntax = '/((?:(?:'.Regex::QuotedString.')\s*)+)(?:,\s*directory:\s*('.Regex::QuotedString.'))?/';

    protected const EntryPointsSyntax = '/('.Regex::QuotedString.')/';

    protected ?string $directory;

    protected array $entrypoints;

    public static function tagName(): string
    {
        return 'vite';
    }

    public function parse(ParseContext $parseContext, Tokenizer $tokenizer): static
    {
        if (! preg_match(static::FullSyntax, $this->markup, $matches)) {
            throw new SyntaxException('Syntax Error in "vite" - Valid syntax: vite "entrypoint1" "entrypoint2", directory: "custom"');
        }

        $directory = isset($matches[2]) ? $this->parseExpression($parseContext, $matches[2]) : null;
        assert($directory === null || is_string($directory));
        $this->directory = $directory;

        if (! preg_match_all(static::EntryPointsSyntax, $matches[1], $matches)) {
            throw new SyntaxException('Syntax Error in "vite" - Valid syntax: vite "entrypoint1" "entrypoint2", directory: "custom"');
        }

        $this->entrypoints = array_map(
            fn (string $match) => $this->parseExpression($parseContext, $match),
            $matches[1],
        );

        return $this;
    }

    public function render(Context $context): string
    {
        $vite = Container::getInstance()->make(Vite::class);
        assert($vite instanceof Vite);

        return $vite($this->entrypoints, $this->directory)->toHtml();
    }
}
