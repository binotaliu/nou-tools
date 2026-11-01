<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\DataTransferObjects;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

final class PostStickyNoteData extends Data
{
    public function __construct(
        public ?string $text,
        public string $paperColor,
        public float $x,
        public float $y,
        public int $rotation,
        public ?UploadedFile $drawing = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        $size = (int) config('cafe.notes.drawing_size');

        return [
            'text' => ['nullable', 'string', 'max:'.(int) config('cafe.notes.max_text_length'), 'required_without:drawing'],
            'paperColor' => ['required', 'string', 'in:'.implode(',', (array) config('cafe.notes.paper_colors'))],
            'x' => ['required', 'numeric', 'between:0,1'],
            'y' => ['required', 'numeric', 'between:0,1'],
            'rotation' => ['required', 'integer', 'between:-8,8'],
            'drawing' => [
                'nullable',
                'file',
                'mimes:png',
                'max:'.(int) config('cafe.notes.drawing_max_kb'),
                "dimensions:width={$size},height={$size}",
                'required_without:text',
            ],
        ];
    }
}
