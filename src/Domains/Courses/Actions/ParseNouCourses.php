<?php

namespace NouTools\Domains\Courses\Actions;

use App\Enums\CourseClassType;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class ParseNouCourses
{
    public function __invoke(string $html, CourseClassType $type): array
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

            $courseName = $this->extractCourseName(trim($titleNode->textContent));

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

    private function getCardsForSection(DOMXPath $xpath, CourseClassType $type): array
    {
        $sectionHeading = $type->sectionHeading();

        if ($sectionHeading !== null) {
            $sectionRow = $this->findSectionRow($xpath, $sectionHeading);

            if ($sectionRow !== null) {
                $cards = $xpath->query('.//div[contains(@class, "card") and contains(@class, "h-100")]', $sectionRow);

                return $cards !== false ? iterator_to_array($cards) : [];
            }

            return [];
        }

        $cards = $xpath->query('//div[contains(@class, "card") and contains(@class, "h-100")]');

        if ($cards === false) {
            return [];
        }

        return iterator_to_array($cards);
    }

    private function findSectionRow(DOMXPath $xpath, string $sectionName): ?DOMElement
    {
        $headings = $xpath->query('//h2');

        if ($headings === false) {
            return null;
        }

        foreach ($headings as $heading) {
            if (trim($heading->textContent) !== $sectionName) {
                continue;
            }

            $sibling = $heading->nextSibling;

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

    private function extractCourseName(string $titleText): string
    {
        if (preg_match('/^\d+\.(.+)$/', $titleText, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

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

    private function extractSessionTimeOverrides(string $text): array
    {
        $overrides = [];

        if (preg_match_all('/第([\d、,]+)次[：:]\s*(\d{1,2}:\d{2})\s*[-~]\s*(\d{1,2}:\d{2})/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $sessionNumbers = preg_split('/[、,]/u', $match[1]);

                foreach ($sessionNumbers as $sessionNumber) {
                    $session = (int) trim($sessionNumber);

                    if ($session > 0) {
                        $overrides[$session] = [
                            'start_time' => $match[2],
                            'end_time' => $match[3],
                        ];
                    }
                }
            }
        }

        return $overrides;
    }

    private function parseClassFooter(DOMNode $footer, DOMXPath $xpath, CourseClassType $type, ?array $defaultTime, array $sessionTimeOverrides): ?array
    {
        $imgNode = $xpath->query('.//class_icon//img', $footer)->item(0);

        if (! $imgNode) {
            return null;
        }

        if (! $imgNode instanceof DOMElement) {
            return null;
        }

        $code = $this->extractClassCode($imgNode->getAttribute('alt'));

        if ($code === '') {
            return null;
        }

        $linkNode = $xpath->query('.//class_icon//a', $footer)->item(0);
        $teacherNode = $xpath->query('.//c1text', $footer)->item(0);
        $dateNode = $xpath->query('.//c3text', $footer)->item(0);
        $time = $defaultTime ?? $type->defaultTimeSlot() ?? ['start' => '00:00', 'end' => '00:00'];

        return [
            'code' => $code,
            'type' => $type,
            'start_time' => $time['start'],
            'end_time' => $time['end'],
            'teacher_name' => $teacherNode ? $this->extractTeacherName(trim($teacherNode->textContent)) : '',
            'link' => $linkNode instanceof DOMElement ? $linkNode->getAttribute('href') : '',
            'dates' => $dateNode ? $this->extractDates(trim($dateNode->textContent)) : [],
            'schedule_time_overrides' => $sessionTimeOverrides,
        ];
    }

    private function extractClassCode(string $alt): string
    {
        if (preg_match('/(zzz\d+)/', $alt, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function extractTeacherName(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        if (preg_match('/^(.+?老師)/', $text, $matches)) {
            return trim($matches[1]);
        }

        return trim($text);
    }

    private function extractDates(string $text): array
    {
        preg_match_all('/(\d{2}\/\d{2})/', $text, $matches);

        return $matches[1] ?? [];
    }
}
