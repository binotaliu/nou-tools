<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Development helper: fills every empty seat on a floor with throwaway
 * "test students" so the next floor's opening logic can be exercised
 * locally without 24 real browsers. Each occupant gets its own schedule
 * and profile, tagged by name so `ReleaseTestStudents` can find and remove
 * them again. Roughly half of them get a running pomodoro so the map also
 * shows timers and thought bubbles.
 */
final readonly class FillFloorWithTestStudents
{
    public const string SCHEDULE_NAME_PREFIX = '自習室測試同學';

    public function __construct(
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    /**
     * @return int number of seats filled
     */
    public function __invoke(int $floor): int
    {
        /** @var array<int, StudyRoomSeat> $filledSeats */
        $filledSeats = DB::transaction(function () use ($floor): array {
            $emptySeats = StudyRoomSeat::query()
                ->where('floor', $floor)
                ->whereNull('student_schedule_id')
                ->orderBy('id')
                ->get();

            $filled = [];

            foreach ($emptySeats as $index => $seat) {
                $number = $index + 1;

                $schedule = new StudentSchedule;
                $schedule->uuid = (string) Str::uuid();
                $schedule->name = self::SCHEDULE_NAME_PREFIX." {$seat->code}";
                $schedule->saveOrFail();

                $profile = new StudyRoomProfile;
                $profile->student_schedule_id = $schedule->id;
                $profile->nickname = Str::limit("測試{$number}號", (int) config('study-room.nickname.max_length'), '');
                $profile->emoji = fake()->randomElement(config('study-room.emojis'));
                $profile->saveOrFail();

                $seat->student_schedule_id = $schedule->id;
                $seat->occupied_at = Date::now();
                $seat->last_seen_at = Date::now();

                if ($number % 2 === 0) {
                    $focusMinutes = (int) config('study-room.timer.pomodoro.focus_minutes');

                    $seat->activity_verb = fake()->randomElement(StudyActivityVerb::cases());
                    $seat->subject_label = fake()->randomElement(['國文', '經濟學', '心理學', '資訊科學導論', '英文']);
                    $seat->timer_mode = StudyTimerMode::Pomodoro;
                    $seat->timer_phase = StudyTimerPhase::Focus;
                    $seat->timer_round = random_int(1, 4);
                    $seat->timer_started_at = Date::now();
                    $seat->timer_ends_at = Date::now()->addMinutes(random_int(1, $focusMinutes));
                } else {
                    $seat->no_timer_since = Date::now();
                }

                $seat->saveOrFail();
                $filled[] = $seat;
            }

            return $filled;
        });

        foreach ($filledSeats as $seat) {
            ($this->broadcastStudyRoomChange)('seat.taken', $seat);
        }

        return count($filledSeats);
    }
}
