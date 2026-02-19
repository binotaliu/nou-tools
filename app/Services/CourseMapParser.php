<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class CourseMapParser
{
    /**
     * Parse course map HTML and extract course information.
     *
     * @return array<int, array{name: string, description_url: string|null, credit_type: string, credits: int, department: string, in_person_class_type: string|null, media: string, multimedia_url: string|null, nature: string}>
     */
    public function parse(string $html): array
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Find all course rows in the result table
        $courseRows = $xpath->query('//tr[td[1]//a[@name="contentdata"]]');

        $courses = [];

        foreach ($courseRows as $row) {
            $cells = $xpath->query('td', $row);

            if ($cells->count() < 9) {
                continue;
            }

            // Extract course name from first cell link
            $linkNode = $xpath->query('a[@name="contentdata"]', $cells->item(0))->item(0);
            if ($linkNode === null) {
                continue;
            }

            $courseName = trim($linkNode->textContent ?? '');
            $descriptionUrl = $linkNode->getAttribute('href');
            if (strpos($descriptionUrl, 'http') !== 0) {
                // URL is relative, prepend the base URL
                $descriptionUrl = 'https://coursemap.nou.edu.tw/'.ltrim($descriptionUrl, './');
            }

            $creditType = trim($cells->item(2)->textContent ?? '');
            $credits = (int) trim($cells->item(3)->textContent ?? '0');
            $department = trim($cells->item(4)->textContent ?? '');
            $inPersonClassType = trim($cells->item(5)->textContent ?? '');
            $media = trim($cells->item(6)->textContent ?? '');
            $nature = trim($cells->item(8)->textContent ?? '');

            // Extract multimedia URL if available
            $multimediaUrl = null;
            $linkNodes = $xpath->query('a', $cells->item(7));
            if ($linkNodes->count() > 0) {
                $multimediaUrl = $linkNodes->item(0)->getAttribute('href');
            }

            $courses[] = [
                'name' => $courseName,
                'description_url' => ! empty($descriptionUrl) ? $descriptionUrl : null,
                'credit_type' => $creditType,
                'credits' => $credits,
                'department' => $department,
                'in_person_class_type' => ! empty($inPersonClassType) ? $inPersonClassType : null,
                'media' => $media,
                'multimedia_url' => $multimediaUrl,
                'nature' => $nature,
            ];
        }

        return $courses;
    }
}
