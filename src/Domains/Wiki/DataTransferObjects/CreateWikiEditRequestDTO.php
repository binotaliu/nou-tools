<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\DataTransferObjects;

use App\Models\WikiPage;
use Closure;
use Illuminate\Http\UploadedFile;
use NouTools\Domains\Wiki\Support\WikiSourceDomains;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * A visitor's request to change a page (`pageSlug`) or create one (no
 * `pageSlug`; `requestedSlug`/`requestedTitle` are optional hints).
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CreateWikiEditRequestDTO extends Data
{
    /**
     * @param  list<string>  $sourceUrls
     * @param  list<UploadedFile>  $screenshots
     */
    public function __construct(
        public string $instruction,
        public array $sourceUrls = [],
        public array $screenshots = [],
        public ?string $pageSlug = null,
        public ?string $requestedSlug = null,
        public ?string $requestedTitle = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'instruction' => ['required', 'string', 'min:5', 'max:'.config('wiki.instruction_max_length')],
            // `sometimes`: an empty list vanishes from a multipart upload.
            'source_urls' => ['sometimes', 'array', 'max:'.config('wiki.max_references')],
            'source_urls.*' => ['required', 'string', 'url:http,https', 'max:500', function (string $attribute, mixed $value, Closure $fail): void {
                if (! WikiSourceDomains::allows((string) $value)) {
                    $fail('來源網址只接受這些網站：'.implode('、', WikiSourceDomains::all()).'。');
                }
            }],
            'screenshots' => ['sometimes', 'array', 'max:'.config('wiki.max_references'), function (string $attribute, mixed $value, Closure $fail) use ($context): void {
                $total = count((array) $value) + count((array) ($context->payload['source_urls'] ?? []));

                if ($total > (int) config('wiki.max_references')) {
                    $fail('來源網址與截圖合計最多 '.config('wiki.max_references').' 項。');
                }
            }],
            'screenshots.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('wiki.screenshot_max_kb')],
            'page_slug' => ['nullable', 'string', 'max:'.WikiPage::SLUG_MAX_LENGTH],
            'requested_slug' => ['nullable', 'string', 'max:'.WikiPage::SLUG_MAX_LENGTH, 'regex:'.WikiPage::SLUG_PATTERN],
            'requested_title' => ['nullable', 'string', 'max:'.config('wiki.max_title_length')],
        ];
    }

    public static function attributes(): array
    {
        return [
            'instruction' => '修改說明',
            'source_urls' => '來源網址',
            'source_urls.*' => '來源網址',
            'screenshots' => '截圖',
            'screenshots.*' => '截圖',
            'requested_slug' => '網址代稱',
            'requested_title' => '標題',
        ];
    }
}
