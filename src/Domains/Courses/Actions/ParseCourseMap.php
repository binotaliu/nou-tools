<?php

namespace NouTools\Domains\Courses\Actions;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class ParseCourseMap
{
    public function __invoke(string $html): array
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $courseRows = $xpath->query('//tr[td[1]//a[@name="contentdata"]]');
        $courses = [];

        foreach ($courseRows as $row) {
            $cells = $xpath->query('td', $row);

            if ($cells->count() < 9) {
                continue;
            }

            $linkNode = $xpath->query('a[@name="contentdata"]', $cells->item(0))->item(0);

            if ($linkNode === null) {
                continue;
            }

            if (! $linkNode instanceof DOMElement) {
                continue;
            }

            $descriptionUrl = $linkNode->getAttribute('href');

            if (strpos($descriptionUrl, 'http') !== 0) {
                $descriptionUrl = 'https://coursemap.nou.edu.tw/'.ltrim($descriptionUrl, './');
            }

            $linkNodes = $xpath->query('a', $cells->item(7));

            $multimediaUrl = null;

            if ($linkNodes->count() > 0) {
                $firstLink = $linkNodes->item(0);

                if ($firstLink instanceof DOMElement) {
                    $multimediaUrl = $firstLink->getAttribute('href');
                }
            }

            $courses[] = [
                'name' => trim($linkNode->textContent ?? ''),
                'description_url' => $descriptionUrl !== '' ? $descriptionUrl : null,
                'credit_type' => trim($cells->item(2)->textContent ?? ''),
                'credits' => (int) trim($cells->item(3)->textContent ?? '0'),
                'department' => trim($cells->item(4)->textContent ?? ''),
                'in_person_class_type' => ($value = trim($cells->item(5)->textContent ?? '')) !== '' ? $value : null,
                'media' => trim($cells->item(6)->textContent ?? ''),
                'multimedia_url' => $multimediaUrl,
                'nature' => trim($cells->item(8)->textContent ?? ''),
            ];
        }

        return $courses;
    }
}
