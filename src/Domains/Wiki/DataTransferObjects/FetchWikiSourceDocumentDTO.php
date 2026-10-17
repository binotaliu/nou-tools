<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\DataTransferObjects;

use App\Models\WikiSourceDocument;
use Closure;
use NouTools\Domains\Wiki\Support\WikiSourceDomains;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * A school document to fetch and index: `series` is its kind across
 * semesters, `term` the semester (null for a document not tied to one).
 */
final class FetchWikiSourceDocumentDTO extends Data
{
    public function __construct(
        public string $url,
        public string $series,
        public ?string $term,
        public string $title,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'url' => ['required', 'string', 'url:http,https', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (! WikiSourceDomains::allows((string) $value)) {
                    $fail('網址只接受這些網站：'.implode('、', WikiSourceDomains::all()).'。');
                }
            }],
            'series' => ['required', 'string', 'max:'.WikiSourceDocument::SERIES_MAX_LENGTH, 'regex:'.WikiSourceDocument::SERIES_PATTERN],
            'term' => ['nullable', 'string', 'regex:'.WikiSourceDocument::TERM_PATTERN],
            'title' => ['required', 'string', 'max:255'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'url' => '網址',
            'series' => '系列',
            'term' => '學期',
            'title' => '標題',
        ];
    }
}
