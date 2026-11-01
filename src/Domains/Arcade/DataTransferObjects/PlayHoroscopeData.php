<?php

declare(strict_types=1);

namespace NouTools\Domains\Arcade\DataTransferObjects;

use App\Enums\ZodiacSign;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

final class PlayHoroscopeData extends Data
{
    public function __construct(
        public ZodiacSign $sign,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'sign' => ['required', 'string', Rule::enum(ZodiacSign::class)],
        ];
    }
}
