<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Support;

use League\CommonMark\Node\Node;
use League\CommonMark\Node\StringContainerHelper;

/**
 * Shared helpers for pulling plain text out of a run of already-parsed
 * inline nodes, used by the `:::cards` / `:::timeline` renderers which need
 * to inspect inline structure (links, bold labels) rather than just render it.
 */
final class InlineTextExtractor
{
    public static function plainText(Node $node): string
    {
        return StringContainerHelper::getChildText($node);
    }

    /**
     * Concatenate the plain text of a node and all of its following siblings.
     */
    public static function plainTextFrom(?Node $node): string
    {
        $text = '';

        for ($n = $node; $n !== null; $n = $n->next()) {
            $text .= self::plainText($n);
        }

        return $text;
    }

    public static function stripLeadingSeparator(string $text): string
    {
        return \preg_replace('/^\s*[—–\-：:]\s*/u', '', $text) ?? $text;
    }
}
