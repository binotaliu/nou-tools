<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use Illuminate\Support\Str;

/**
 * Splits a document's page texts into sections the editor can read one at a
 * time. NOU's notices have two heading styles: numbered items of the main
 * text (`一、…`, `二、…`, in order) and appendices on a line of their own
 * (`【附錄三】`, the title on the same or the next line). Numbered items
 * only count in sequence and never inside an appendix, since appendices
 * carry numbered lists of their own. A document with fewer than two such
 * headings falls back to fixed chunks of pages, and a section over
 * `MAX_SECTION_CHARS` is cut at page boundaries.
 */
final readonly class SplitWikiSourceText
{
    public const MAX_SECTION_CHARS = 30_000;

    public const FALLBACK_PAGES_PER_SECTION = 5;

    private const NUMERALS = '一二三四五六七八九十廿';

    /**
     * @param  list<string>  $pages  text per page, page 1 first
     * @return list<array{key: string, title: string, body: string, page_start: int, page_end: int}>
     */
    public function __invoke(array $pages): array
    {
        while ($pages !== [] && trim((string) end($pages)) === '') {
            array_pop($pages);
        }

        $sections = $this->splitOnHeadings($pages);

        if ($sections === null) {
            return $this->chunkByPages($pages);
        }

        $result = [];
        $usedKeys = [];

        foreach ($sections as $section) {
            foreach ($this->capSize($section) as $part) {
                $part['key'] = $this->uniqueKey($part['key'], $usedKeys);
                $result[] = $part;
            }
        }

        return $result;
    }

    /**
     * @param  list<string>  $pages
     * @return list<array{key: string, title: string, pages: array<int, list<string>>}>|null null when there are too few headings
     */
    private function splitOnHeadings(array $pages): ?array
    {
        $sections = [['key' => 'preface', 'title' => '前言與目次', 'pages' => []]];
        $headings = 0;
        $nextPart = 1;
        $inAppendix = false;
        $titlePending = false;

        foreach ($pages as $index => $text) {
            foreach (explode("\n", $text) as $line) {
                $heading = $this->appendixHeading($line) ?? ($inAppendix ? null : $this->partHeading($line, $nextPart));

                if ($heading !== null) {
                    $sections[] = ['key' => $heading['key'], 'title' => $heading['title'], 'pages' => []];
                    $headings++;
                    $titlePending = $heading['titlePending'];

                    if ($heading['appendix']) {
                        $inAppendix = true;
                    } else {
                        $nextPart++;
                    }
                } elseif ($titlePending && trim($line) !== '') {
                    $sections[array_key_last($sections)]['title'] .= ' '.$this->clean($line);
                    $titlePending = false;
                }

                $sections[array_key_last($sections)]['pages'][$index + 1][] = $line;
            }
        }

        return $headings >= 2 ? $sections : null;
    }

    /**
     * @return array{key: string, title: string, appendix: true, titlePending: bool}|null
     */
    private function appendixHeading(string $line): ?array
    {
        if (preg_match('/^\s*【附錄(['.self::NUMERALS.']+)】\s*(.*)$/u', $line, $match) !== 1) {
            return null;
        }

        $number = $this->numeral($match[1]);
        $title = $this->clean($match[2]);

        return [
            'key' => 'appendix-'.$number,
            'title' => trim('附錄'.$match[1].' '.$title),
            'appendix' => true,
            'titlePending' => $title === '',
        ];
    }

    /**
     * @return array{key: string, title: string, appendix: false, titlePending: false}|null
     */
    private function partHeading(string $line, int $expected): ?array
    {
        if (preg_match('/^ {0,2}(['.self::NUMERALS.']+)、\s*(\S.*)$/u', $line, $match) !== 1 || $this->numeral($match[1]) !== $expected) {
            return null;
        }

        // The title is the clause before the first colon or full stop; what
        // follows on the line belongs to the section's text.
        $title = $match[1].'、'.Str::before($this->clean($match[2]), '：');

        return [
            'key' => 'part-'.$expected,
            'title' => Str::limit(Str::before($title, '。'), 100, ''),
            'appendix' => false,
            'titlePending' => false,
        ];
    }

    /**
     * @param  array{key: string, title: string, pages: array<int, list<string>>}  $section
     * @return list<array{key: string, title: string, body: string, page_start: int, page_end: int}>
     */
    private function capSize(array $section): array
    {
        $groups = [];
        $current = [];
        $length = 0;

        foreach ($section['pages'] as $number => $lines) {
            $text = $this->body($lines);

            if ($text === '') {
                continue;
            }

            if ($current !== [] && $length + strlen($text) > self::MAX_SECTION_CHARS) {
                $groups[] = $current;
                $current = [];
                $length = 0;
            }

            $current[$number] = $text;
            $length += strlen($text);
        }

        if ($current !== []) {
            $groups[] = $current;
        }

        $parts = [];

        foreach ($groups as $position => $group) {
            $first = array_key_first($group);
            $last = array_key_last($group);

            $parts[] = [
                'key' => $position === 0 ? $section['key'] : $section['key'].'-'.($position + 1),
                'title' => count($groups) > 1 ? Str::limit($section['title'], 90, '').'（第 '.$first.'–'.$last.' 頁）' : $section['title'],
                'body' => implode("\n", $group),
                'page_start' => $first,
                'page_end' => $last,
            ];
        }

        return $parts;
    }

    /**
     * @param  list<string>  $pages
     * @return list<array{key: string, title: string, body: string, page_start: int, page_end: int}>
     */
    private function chunkByPages(array $pages): array
    {
        $chunks = [];

        foreach (array_chunk($pages, self::FALLBACK_PAGES_PER_SECTION, true) as $chunk) {
            $body = $this->body(explode("\n", implode("\n", $chunk)));

            if ($body === '') {
                continue;
            }

            $start = array_key_first($chunk) + 1;
            $end = array_key_last($chunk) + 1;

            $chunks[] = [
                'key' => 'pages-'.$start.'-'.$end,
                'title' => '第 '.$start.'–'.$end.' 頁',
                'body' => $body,
                'page_start' => $start,
                'page_end' => $end,
            ];
        }

        return $chunks;
    }

    /**
     * @param  list<string>  $lines
     */
    private function body(array $lines): string
    {
        $text = (string) preg_replace('/[ \t]+$/m', '', implode("\n", $lines));

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * Collapses whitespace, including the spaces some headings put between
     * every Han character (`國 立 空 中 大 學`).
     */
    private function clean(string $text): string
    {
        $text = (string) preg_replace('/(?<=\p{Han})\s+(?=\p{Han})/u', '', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param  array<string, true>  $usedKeys
     */
    private function uniqueKey(string $key, array &$usedKeys): string
    {
        $unique = $key;

        for ($suffix = 2; isset($usedKeys[$unique]); $suffix++) {
            $unique = $key.'-dup'.$suffix;
        }

        $usedKeys[$unique] = true;

        return $unique;
    }

    /**
     * 一 … 十九, 二十, 廿, 廿一 …
     */
    private function numeral(string $numeral): int
    {
        $digits = array_flip(mb_str_split('一二三四五六七八九'));
        $characters = mb_str_split($numeral);
        $tens = 0;
        $units = 0;

        foreach ($characters as $position => $character) {
            if ($character === '廿') {
                $tens = 2;
            } elseif ($character === '十') {
                $tens = isset($digits[$characters[$position - 1] ?? '']) ? $digits[$characters[$position - 1]] + 1 : 1;
                $units = 0;
            } else {
                $units = $digits[$character] + 1;
            }
        }

        return $tens * 10 + $units;
    }
}
