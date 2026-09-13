<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\DataTransferObjects;

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerMode;
use Illuminate\Validation\Rule;
use NouTools\Domains\StudyRoom\DataTransferObjects\Concerns\ValidatesSubjectCourseId;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class StartStudyTimerData extends Data
{
    use ValidatesSubjectCourseId;

    public function __construct(
        public StudyTimerMode $mode,
        public ?int $minutes,
        public StudyActivityVerb $verb,
        public ?int $subjectCourseId,
        public ?int $focusMinutes = null,
        public ?int $shortBreakMinutes = null,
        public ?int $longBreakMinutes = null,
        public ?int $roundsPerCycle = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $minMinutes = (int) config('study-room.timer.custom.min_minutes');
        $maxMinutes = (int) config('study-room.timer.custom.max_minutes');
        $mode = $context->payload['mode'] ?? null;

        [$focusMin, $focusMax] = config('study-room.timer.pomodoro.bounds.focus_minutes');
        [$breakMin, $breakMax] = config('study-room.timer.pomodoro.bounds.break_minutes');
        [$roundsMin, $roundsMax] = config('study-room.timer.pomodoro.bounds.rounds_per_cycle');

        return [
            'mode' => ['required', Rule::enum(StudyTimerMode::class)],
            'minutes' => [
                Rule::requiredIf($mode === StudyTimerMode::Custom->value),
                'nullable',
                'integer',
                "min:{$minMinutes}",
                "max:{$maxMinutes}",
            ],
            'verb' => ['required', Rule::enum(StudyActivityVerb::class)],
            'subjectCourseId' => ['nullable', 'integer', self::belongsToViewerScheduleRule()],
            'focusMinutes' => ['nullable', 'integer', "min:{$focusMin}", "max:{$focusMax}"],
            'shortBreakMinutes' => ['nullable', 'integer', "min:{$breakMin}", "max:{$breakMax}"],
            'longBreakMinutes' => ['nullable', 'integer', "min:{$breakMin}", "max:{$breakMax}"],
            'roundsPerCycle' => ['nullable', 'integer', "min:{$roundsMin}", "max:{$roundsMax}"],
        ];
    }

    public static function attributes(): array
    {
        return [
            'mode' => '計時模式',
            'minutes' => '分鐘數',
            'verb' => '活動',
            'subjectCourseId' => '科目',
            'focusMinutes' => '專注分鐘數',
            'shortBreakMinutes' => '短休息分鐘數',
            'longBreakMinutes' => '長休息分鐘數',
            'roundsPerCycle' => '每輪循環次數',
        ];
    }

    /**
     * Whether the request carries a full pomodoro cycle to save as the
     * student's preference. All four or nothing: a partial cycle is ignored
     * rather than half-overwriting what they had.
     */
    public function hasPomodoroCycle(): bool
    {
        return $this->focusMinutes !== null
            && $this->shortBreakMinutes !== null
            && $this->longBreakMinutes !== null
            && $this->roundsPerCycle !== null;
    }

    public function pomodoroCycle(): PomodoroCycle
    {
        return new PomodoroCycle(
            focusMinutes: (int) $this->focusMinutes,
            shortBreakMinutes: (int) $this->shortBreakMinutes,
            longBreakMinutes: (int) $this->longBreakMinutes,
            roundsPerCycle: (int) $this->roundsPerCycle,
        );
    }
}
