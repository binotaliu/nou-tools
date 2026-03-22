<?php

namespace NouTools\Domains\Courses\ViewModels\Api;

use App\Models\Textbook;
use Spatie\LaravelData\Data;

/**
 * Textbook information for a course (參考書).
 */
final class TextbookViewModel extends Data
{
    public function __construct(
        public string $bookTitle,
        public ?string $edition,
        public ?string $priceInfo,
        public ?string $referenceUrl,
    ) {}

    public static function fromModel(Textbook $textbook): self
    {
        return new self(
            bookTitle: $textbook->book_title,
            edition: $textbook->edition,
            priceInfo: $textbook->price_info,
            referenceUrl: $textbook->reference_url,
        );
    }
}
