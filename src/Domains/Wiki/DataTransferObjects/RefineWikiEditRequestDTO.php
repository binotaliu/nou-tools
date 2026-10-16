<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\DataTransferObjects;

use Spatie\LaravelData\Data;

final class RefineWikiEditRequestDTO extends Data
{
    public function __construct(
        public string $instruction,
    ) {}

    public static function rules(): array
    {
        return [
            'instruction' => ['required', 'string', 'min:2', 'max:'.config('wiki.instruction_max_length')],
        ];
    }

    public static function attributes(): array
    {
        return [
            'instruction' => '調整說明',
        ];
    }
}
