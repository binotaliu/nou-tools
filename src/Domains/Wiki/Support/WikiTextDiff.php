<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Support;

/**
 * Line-level comparison of two page bodies (longest common subsequence),
 * enough to size an edit for the auto-publish rules. Pages are capped at
 * `wiki.max_body_chars`, so the quadratic table stays small.
 */
final readonly class WikiTextDiff
{
    /**
     * @param  list<string>  $addedLines
     * @param  list<string>  $removedLines
     */
    private function __construct(
        public array $addedLines,
        public array $removedLines,
        public int $baseLineCount,
        public int $newLineCount,
    ) {}

    public static function compare(string $base, string $new): self
    {
        $baseLines = self::lines($base);
        $newLines = self::lines($new);
        $baseCount = count($baseLines);
        $newCount = count($newLines);

        // lengths[i][j] = LCS length of baseLines[i..] and newLines[j..]
        $lengths = array_fill(0, $baseCount + 1, array_fill(0, $newCount + 1, 0));

        for ($i = $baseCount - 1; $i >= 0; $i--) {
            for ($j = $newCount - 1; $j >= 0; $j--) {
                $lengths[$i][$j] = $baseLines[$i] === $newLines[$j]
                    ? $lengths[$i + 1][$j + 1] + 1
                    : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }

        $added = [];
        $removed = [];
        $i = 0;
        $j = 0;

        while ($i < $baseCount && $j < $newCount) {
            if ($baseLines[$i] === $newLines[$j]) {
                $i++;
                $j++;
            } elseif ($lengths[$i + 1][$j] >= $lengths[$i][$j + 1]) {
                $removed[] = $baseLines[$i++];
            } else {
                $added[] = $newLines[$j++];
            }
        }

        array_push($removed, ...array_slice($baseLines, $i));
        array_push($added, ...array_slice($newLines, $j));

        return new self($added, $removed, $baseCount, $newCount);
    }

    /**
     * Added plus removed lines, over the longer of the two versions.
     */
    public function changedRatio(): float
    {
        $longest = max($this->baseLineCount, $this->newLineCount, 1);

        return (count($this->addedLines) + count($this->removedLines)) / $longest;
    }

    /**
     * Characters on removed lines that don't come back on an added line,
     * i.e. how much text an edit takes away rather than rewords.
     */
    public function removedChars(): int
    {
        $removed = mb_strlen(implode('', $this->removedLines));
        $added = mb_strlen(implode('', $this->addedLines));

        return max(0, $removed - $added);
    }

    /**
     * Non-blank lines; blank-line shuffles are not edits worth counting.
     *
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        return array_values(array_filter(
            array_map('rtrim', explode("\n", str_replace("\r\n", "\n", $text))),
            fn (string $line): bool => $line !== '',
        ));
    }
}
