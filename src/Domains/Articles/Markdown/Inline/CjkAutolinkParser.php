<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Inline;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * GFM's `UrlAutolinkParser` only turns a bare URL into a link when the
 * preceding character is whitespace, line-start, or a small set of ASCII
 * delimiters (`*_~(`). Chinese prose routinely butts punctuation straight up
 * against a URL (`示範：https://example.com`, `看看這個網站https://example.com`),
 * so those bare URLs were silently left as plain text.
 *
 * This parser fires on the exact same trigger prefixes but is registered at
 * a lower priority than GFM's, so it only ever gets a turn when GFM's parser
 * has already declined the match - which, in practice, only happens because
 * of the preceding-character restriction. It then re-checks that restriction
 * itself, extending it to CJK ideographs and CJK/full-width punctuation.
 *
 * Because this is a normal `InlineParserInterface` (not a raw-text scan), it
 * automatically inherits the same "no linking inside code spans / fenced code
 * / existing markdown link destinations" behaviour as GFM's own parser: those
 * character ranges are consumed by other parsers before this one ever sees
 * them.
 */
final class CjkAutolinkParser implements InlineParserInterface
{
    private const URL_PATTERN = '/^(?:https?:\/\/|www\.)[!-~]+/';

    private const CJK_PATTERN = '/[\x{3000}-\x{303F}\x{FF00}-\x{FFEF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u';

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::oneOf('http://', 'https://', 'www.');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $previousChar = $cursor->peek(-1);

        if ($previousChar === null || ! $this->isCjk($previousChar)) {
            return false;
        }

        if (! \preg_match(self::URL_PATTERN, $cursor->getRemainder(), $matches)) {
            return false;
        }

        $url = $this->trimUnmatchedParens($this->trimTrailingPunctuation($matches[0]));

        if ($url === '') {
            return false;
        }

        $cursor->advanceBy(\mb_strlen($url, 'UTF-8'));

        $href = \str_starts_with($url, 'www.') ? 'http://'.$url : $url;
        $inlineContext->getContainer()->appendChild(new Link($href, $url));

        return true;
    }

    private function isCjk(string $character): bool
    {
        return \preg_match(self::CJK_PATTERN, $character) === 1;
    }

    private function trimTrailingPunctuation(string $url): string
    {
        return \preg_replace('/[?!.,:;*_~\'"]+$/', '', $url) ?? $url;
    }

    private function trimUnmatchedParens(string $url): string
    {
        if (! \str_ends_with($url, ')')) {
            return $url;
        }

        $diff = \substr_count($url, ')') - \substr_count($url, '(');

        return $diff > 0 ? \substr($url, 0, -$diff) : $url;
    }
}
