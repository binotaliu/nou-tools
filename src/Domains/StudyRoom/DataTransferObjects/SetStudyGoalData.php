<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\DataTransferObjects;

use Spatie\LaravelData\Data;

/**
 * `dailyGoals` is keyed by ISO weekday (1 = Monday … 7 = Sunday); each
 * entry may carry a `minutes` target, a `remindAt` time, or both.
 */
final class SetStudyGoalData extends Data
{
    /**
     * @param  array<int|string, array{minutes?: int|null, remindAt?: string|null}>  $dailyGoals
     */
    public function __construct(
        public ?int $weeklyGoalMinutes,
        public array $dailyGoals,
    ) {}

    public static function rules(): array
    {
        return [
            'weeklyGoalMinutes' => ['nullable', 'integer', 'min:1', 'max:6000'],
            'dailyGoals' => ['present', 'array'],
            'dailyGoals.*' => ['array'],
            'dailyGoals.*.minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'dailyGoals.*.remindAt' => ['nullable', 'date_format:H:i'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'weeklyGoalMinutes' => '每週目標',
            'dailyGoals' => '每日目標',
            'dailyGoals.*.minutes' => '每日目標分鐘數',
            'dailyGoals.*.remindAt' => '提醒時間',
        ];
    }
}
