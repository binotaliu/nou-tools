<?php

namespace App\Services;

use App\Enums\CourseClassType;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class NouCourseParser
{
    /**
     * Parse HTML content from NOU course page and return structured data.
     *
     * @return list<array{
     *     name: string,
     *     classes: list<array{
     *         code: string,
     *         type: CourseClassType,
     *         start_time: string,
     *         end_time: string,
     *         teacher_name: string,
     *         link: string,
     *         dates: list<string>,
     *         schedule_time_overrides: array<int, array{start_time: string, end_time: string}>
     *     }>
     * }>
     */
    public function parse(string $html, CourseClassType $type): array
    {
        if (trim($html) === '') {
            return [];
        }

        $dom = new DOMDocument;

        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $cards = $this->getCardsForSection($xpath, $type);

        $courses = [];

        foreach ($cards as $card) {
            $titleNode = $xpath->query('.//h4[contains(@class, "card-title")]', $card)->item(0);

            if (! $titleNode) {
                continue;
            }

            $titleText = trim($titleNode->textContent);
            $courseName = $this->extractCourseName($titleText);

            if ($courseName === '') {
                continue;
            }

            $timeNode = $xpath->query('.//h6[contains(@class, "card-subtitle")]', $card)->item(0);
            $timeText = $timeNode ? trim($timeNode->textContent) : '';
            $defaultTime = $this->extractTime($timeText);
            $sessionTimeOverrides = $this->extractSessionTimeOverrides($timeText);

            $footers = $xpath->query('.//div[contains(@class, "card-footer")]', $card);
            $classes = [];

            foreach ($footers as $footer) {
                $classData = $this->parseClassFooter($footer, $xpath, $type, $defaultTime, $sessionTimeOverrides);

                if ($classData !== null) {
                    $classes[] = $classData;
                }
            }

            if ($classes !== []) {
                $courses[] = [
                    'name' => $courseName,
                    'classes' => $classes,
                ];
            }
        }

        return $courses;
    }

    /**
     * Get the relevant card elements based on the course type section.
     *
     * For FullRemote and MicroCredit types, filters to cards within the
     * matching section (identified by h2 headings). Returns empty if the
     * section heading exists in the enum but is not found in the HTML.
     * Falls back to all cards only for types without section headings.
     *
     * @return list<DOMNode>
     */
    private function getCardsForSection(DOMXPath $xpath, CourseClassType $type): array
    {
        $sectionHeading = $type->sectionHeading();

        if ($sectionHeading !== null) {
            $sectionRow = $this->findSectionRow($xpath, $sectionHeading);

            if ($sectionRow !== null) {
                $cards = $xpath->query('.//div[contains(@class, "card") and contains(@class, "h-100")]', $sectionRow);

                return $cards !== false ? iterator_to_array($cards) : [];
            }

            // Section heading expected but not found — no courses for this type
            return [];
        }

        // No section filtering needed — return all cards
        $cards = $xpath->query('//div[contains(@class, "card") and contains(@class, "h-100")]');

        if ($cards === false) {
            return [];
        }

        return iterator_to_array($cards);
    }

    /**
     * Find the row element that follows the h2 heading matching the given section name.
     */
    private function findSectionRow(DOMXPath $xpath, string $sectionName): ?DOMElement
    {
        $h2Nodes = $xpath->query('//h2');

        if ($h2Nodes === false) {
            return null;
        }

        foreach ($h2Nodes as $h2) {
            if (trim($h2->textContent) !== $sectionName) {
                continue;
            }

            // Walk siblings to find the next div.row
            $sibling = $h2->nextSibling;

            while ($sibling !== null) {
                if (
                    $sibling instanceof DOMElement
                    && $sibling->tagName === 'div'
                    && str_contains($sibling->getAttribute('class'), 'row')
                ) {
                    return $sibling;
                }

                $sibling = $sibling->nextSibling;
            }

            break;
        }

        return null;
    }

    /**
     * Extract course name from title text like "01.做伙唱歌學台語".
     */
    private function extractCourseName(string $titleText): string
    {
        if (preg_match('/^\d+\.(.+)$/', $titleText, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Extract time range from text like "時間：09:00-10:50".
     * Only extracts the first (default) time, ignoring any session overrides.
     *
     * @return array{start: string, end: string}|null
     */
    private function extractTime(string $text): ?array
    {
        if (preg_match('/(\d{1,2}:\d{2})\s*[-~]\s*(\d{1,2}:\d{2})/', $text, $matches)) {
            return [
                'start' => $matches[1],
                'end' => $matches[2],
            ];
        }

        return null;
    }

    /**
     * Extract session-based time overrides from subtitle text.
     *
     * Parses patterns like "第1、2次：18:30-21:00" to build a mapping
     * of 1-indexed session numbers to their override times.
     *
     * @return array<int, array{start_time: string, end_time: string}>
     */
    private function extractSessionTimeOverrides(string $text): array
    {
        $overrides = [];

        if (preg_match_all('/第([\d、,]+)次[：:]\s*(\d{1,2}:\d{2})\s*[-~]\s*(\d{1,2}:\d{2})/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $sessionNumbers = preg_split('/[、,]/u', $match[1]);
                $startTime = $match[2];
                $endTime = $match[3];

                foreach ($sessionNumbers as $sessionStr) {
                    $session = (int) trim($sessionStr);

                    if ($session > 0) {
                        $overrides[$session] = [
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ];
                    }
                }
            }
        }

        return $overrides;
    }

    /**
     * Parse a card-footer element to extract class information.
     *
     * @param  array{start: string, end: string}|null  $defaultTime
     * @param  array<int, array{start_time: string, end_time: string}>  $sessionTimeOverrides
     * @return array{code: string, type: CourseClassType, start_time: string, end_time: string, teacher_name: string, link: string, dates: list<string>, schedule_time_overrides: array<int, array{start_time: string, end_time: string}>}|null
     */
    private function parseClassFooter(
        DOMNode $footer,
        DOMXPath $xpath,
        CourseClassType $type,
        ?array $defaultTime,
        array $sessionTimeOverrides,
    ): ?array {
        $imgNode = $xpath->query('.//class_icon//img', $footer)->item(0);

        if (! $imgNode) {
            return null;
        }

        $code = $this->extractClassCode($imgNode->getAttribute('alt'));

        if ($code === '') {
            return null;
        }

        $linkNode = $xpath->query('.//class_icon//a', $footer)->item(0);
        $link = $linkNode ? $linkNode->getAttribute('href') : '';

        $teacherNode = $xpath->query('.//c1text', $footer)->item(0);
        $teacherName = $teacherNode ? $this->extractTeacherName(trim($teacherNode->textContent)) : '';

        $dateNode = $xpath->query('.//c3text', $footer)->item(0);
        $dates = $dateNode ? $this->extractDates(trim($dateNode->textContent)) : [];

        $time = $defaultTime ?? $type->defaultTimeSlot() ?? ['start' => '00:00', 'end' => '00:00'];

        return [
            'code' => $code,
            'type' => $type,
            'start_time' => $time['start'],
            'end_time' => $time['end'],
            'teacher_name' => $teacherName,
            'link' => $link,
            'dates' => $dates,
            'schedule_time_overrides' => $sessionTimeOverrides,
        ];
    }

    /**
     * Extract class code from img alt like "zzz001班按我進入教室".
     */
    private function extractClassCode(string $alt): string
    {
        if (preg_match('/(zzz\d+)/', $alt, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Extract teacher name from text like "蔡惠名老師".
     */
    private function extractTeacherName(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        if (preg_match('/^(.+?老師)/', $text, $matches)) {
            return trim($matches[1]);
        }

        return trim($text);
    }

    /**
     * Extract dates from text like "03/09、03/30、05/11、06/08".
     *
     * @return list<string>
     */
    private function extractDates(string $text): array
    {
        preg_match_all('/(\d{2}\/\d{2})/', $text, $matches);

        return $matches[1] ?? [];
    }
}
