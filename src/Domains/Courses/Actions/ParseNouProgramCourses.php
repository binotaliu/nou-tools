<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\Actions;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses one 專班 region page under vc.nou.edu.tw/svc/. Each `<h2>` is a
 * 專班, followed by a row of course cards. A card carries one class: the
 * 專班 itself is the "class", with a single shared classroom link.
 */
final readonly class ParseNouProgramCourses
{
    public function __construct(private ParseNouClassText $text = new ParseNouClassText) {}

    /**
     * @return array<int, array{name: string, courses: array<int, array{name: string, start_time: string, end_time: string, teacher_name: string, link: string, dates: array<int, string>, schedule_time_overrides: array<int, array{start_time: string, end_time: string}>}>}>
     */
    public function __invoke(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $dom = new DOMDocument;

        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $programs = [];

        foreach ($xpath->query('//h2') ?: [] as $heading) {
            $name = trim($heading->textContent);
            $row = $this->rowAfter($heading);

            if ($name === '' || $row === null) {
                continue;
            }

            $courses = [];

            foreach ($xpath->query('.//div[contains(@class, "card") and contains(@class, "h-100")]', $row) ?: [] as $card) {
                $course = $this->parseCard($card, $xpath);

                if ($course !== null) {
                    $courses[] = $course;
                }
            }

            if ($courses !== []) {
                $programs[] = ['name' => $name, 'courses' => $courses];
            }
        }

        return $programs;
    }

    private function rowAfter(DOMElement $heading): ?DOMElement
    {
        for ($node = $heading->nextSibling; $node !== null; $node = $node->nextSibling) {
            if (
                $node instanceof DOMElement
                && $node->tagName === 'div'
                && str_contains($node->getAttribute('class'), 'row')
            ) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @return array{name: string, start_time: string, end_time: string, teacher_name: string, link: string, dates: array<int, string>, schedule_time_overrides: array<int, array{start_time: string, end_time: string}>}|null
     */
    private function parseCard(DOMElement $card, DOMXPath $xpath): ?array
    {
        $title = $xpath->query('.//h4[starts-with(@class, "card-title")]', $card)->item(0);
        $name = $title ? $this->text->courseName($title->textContent) : '';

        if ($name === '') {
            return null;
        }

        $subtitle = $xpath->query('.//h6[contains(@class, "card-subtitle")]', $card)->item(0);
        $timeText = $subtitle ? $subtitle->textContent : '';
        $time = $this->text->time($timeText);

        $footer = $xpath->query('.//div[contains(@class, "card-footer")]', $card)->item(0);

        if ($footer === null) {
            return null;
        }

        $link = $xpath->query('.//class_icon//a', $footer)->item(0);
        $teacher = $xpath->query('.//c1text', $footer)->item(0);
        $dates = $xpath->query('.//c3text', $footer)->item(0);

        return [
            'name' => $name,
            'start_time' => $time['start'] ?? '00:00',
            'end_time' => $time['end'] ?? '00:00',
            'teacher_name' => $teacher ? $this->text->teacherName($teacher->textContent) : '',
            'link' => $link instanceof DOMElement ? trim($link->getAttribute('href')) : '',
            'dates' => $dates ? $this->text->dates($dates->textContent) : [],
            'schedule_time_overrides' => $this->text->sessionTimeOverrides($timeText),
        ];
    }
}
