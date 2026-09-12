<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\DataTransferObjects;

use App\Settings\StudyRoomSettings;
use Closure;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class SetStudyRoomProfileData extends Data
{
    public function __construct(
        public string $nickname,
        public string $emoji,
        public bool $playSoundOnTimerEnd,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $minLength = (int) config('study-room.nickname.min_length');
        $maxLength = (int) config('study-room.nickname.max_length');

        return [
            'nickname' => [
                'required',
                'string',
                "min:{$minLength}",
                "max:{$maxLength}",
                // CJK / letters / digits / single spaces only — no control
                // characters, newlines, or URLs (which need characters
                // like ":" and "/" that this pattern excludes).
                'regex:/^[\p{L}\p{N} ]+$/u',
                self::forbiddenNicknameRule(),
            ],
            'emoji' => ['required', 'string', Rule::in(config('study-room.emojis'))],
            'playSoundOnTimerEnd' => ['required', 'boolean'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'nickname' => '暱稱',
            'emoji' => '表情符號',
            'playSoundOnTimerEnd' => '時間到時播放音效',
        ];
    }

    private static function forbiddenNicknameRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $normalized = self::normalizeForComparison((string) $value);

            foreach (app(StudyRoomSettings::class)->forbiddenNicknames as $forbidden) {
                $normalizedForbidden = self::normalizeForComparison($forbidden);

                if ($normalizedForbidden !== '' && str_contains($normalized, $normalizedForbidden)) {
                    $fail('暱稱含有不適當的字詞，請重新輸入。');

                    return;
                }
            }
        };
    }

    private static function normalizeForComparison(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '', $value) ?? $value);
    }
}
