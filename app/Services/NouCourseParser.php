<?php

namespace App\Services;

use App\Enums\CourseClassType;
use DOMDocument;
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
     *         dates: list<string>
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

        $courses = [];
        $cards = $xpath->query('//div[contains(@class, "card") and contains(@class, "h-100")]');

        if ($cards === false) {
            return [];
        }

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
            $defaultTime = $this->extractTime($timeNode ? trim($timeNode->textContent) : '');

            $footers = $xpath->query('.//div[contains(@class, "card-footer")]', $card);
            $classes = [];

            foreach ($footers as $footer) {
                $classData = $this->parseClassFooter($footer, $xpath, $type, $defaultTime);

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
     * Parse a card-footer element to extract class information.
     *
     * @param  array{start: string, end: string}|null  $defaultTime
     * @return array{code: string, type: CourseClassType, start_time: string, end_time: string, teacher_name: string, link: string, dates: list<string>}|null
     */
    private function parseClassFooter(
        \DOMNode $footer,
        DOMXPath $xpath,
        CourseClassType $type,
        ?array $defaultTime,
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
