<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Support;

/**
 * Line-level comparison of two page bodies (longest common subsequence):
 * sizes an edit for the auto-publish rules and feeds the diff views. Pages
 * are capped at `wiki.max_body_chars`, so the quadratic table stays small.
 */
final readonly class WikiTextDiff
{
    public const string EQUAL = 'equal';

    public const string ADDED = 'added';

    public const string REMOVED = 'removed';

    /**
     * @param  list<array{type: string, text: string}>  $operations
     */
    private function __construct(
        public array $operations,
        public int $baseLineCount,
        public int $newLineCount,
    ) {}

    /**
     * @param  bool  $ignoreBlankLines  blank-line shuffles are no edit worth counting, but a diff view shows them
     */
    public static function compare(string $base, string $new, bool $ignoreBlankLines = true): self
    {
        $baseLines = self::lines($base, $ignoreBlankLines);
        $newLines = self::lines($new, $ignoreBlankLines);
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

        $operations = [];
        $i = 0;
        $j = 0;

        while ($i < $baseCount || $j < $newCount) {
            if ($i < $baseCount && $j < $newCount && $baseLines[$i] === $newLines[$j]) {
                $operations[] = ['type' => self::EQUAL, 'text' => $baseLines[$i]];
                $i++;
                $j++;
            } elseif ($j >= $newCount || ($i < $baseCount && $lengths[$i + 1][$j] >= $lengths[$i][$j + 1])) {
                $operations[] = ['type' => self::REMOVED, 'text' => $baseLines[$i++]];
            } else {
                $operations[] = ['type' => self::ADDED, 'text' => $newLines[$j++]];
            }
        }

        return new self($operations, $baseCount, $newCount);
    }

    /**
     * @return list<string>
     */
    public function addedLines(): array
    {
        return $this->textOf(self::ADDED);
    }

    /**
     * @return list<string>
     */
    public function removedLines(): array
    {
        return $this->textOf(self::REMOVED);
    }

    /**
     * Added plus removed lines, over the longer of the two versions.
     */
    public function changedRatio(): float
    {
        $longest = max($this->baseLineCount, $this->newLineCount, 1);

        return (count($this->addedLines()) + count($this->removedLines())) / $longest;
    }

    /**
     * Characters on removed lines that don't come back on an added line,
     * i.e. how much text an edit takes away rather than rewords.
     */
    public function removedChars(): int
    {
        $removed = mb_strlen(implode('', $this->removedLines()));
        $added = mb_strlen(implode('', $this->addedLines()));

        return max(0, $removed - $added);
    }

    /**
     * The operations with long unchanged stretches folded into gaps, keeping
     * `$context` lines around each change, for display.
     *
     * @return list<array{type: string, text: string}> type `gap` carries the number of hidden lines as text
     */
    public function hunks(int $context = 3): array
    {
        $changed = [];

        foreach ($this->operations as $index => $operation) {
            if ($operation['type'] !== self::EQUAL) {
                $changed[] = $index;
            }
        }

        $visible = [];

        foreach ($changed as $index) {
            for ($k = max(0, $index - $context); $k <= min(count($this->operations) - 1, $index + $context); $k++) {
                $visible[$k] = true;
            }
        }

        $lines = [];
        $hidden = 0;

        foreach ($this->operations as $index => $operation) {
            if (isset($visible[$index])) {
                if ($hidden > 0) {
                    $lines[] = ['type' => 'gap', 'text' => (string) $hidden];
                    $hidden = 0;
                }

                $lines[] = $operation;
            } else {
                $hidden++;
            }
        }

        if ($hidden > 0 && $lines !== []) {
            $lines[] = ['type' => 'gap', 'text' => (string) $hidden];
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function textOf(string $type): array
    {
        return array_values(array_map(
            fn (array $operation): string => $operation['text'],
            array_filter($this->operations, fn (array $operation): bool => $operation['type'] === $type),
        ));
    }

    /**
     * @return list<string>
     */
    private static function lines(string $text, bool $ignoreBlankLines): array
    {
        $lines = array_map('rtrim', explode("\n", rtrim(str_replace("\r\n", "\n", $text))));

        return $ignoreBlankLines
            ? array_values(array_filter($lines, fn (string $line): bool => $line !== ''))
            : $lines;
    }
}
