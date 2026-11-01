<?php

declare(strict_types=1);

namespace NouTools\Domains\Arcade\Actions;

use App\Enums\AcornTransactionKind;
use App\Enums\ArcadeGame;
use App\Enums\ZodiacSign;
use App\Models\ArcadePlay;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Actions\SpendAcorns;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\Acorns\Support\AcornExpiry;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Inserts the coin: unlocks a sign's reading for the rest of the Taipei day.
 * Asking again for a sign already unlocked today returns the same play and
 * costs nothing. The schedule row is locked, so two taps at once pay once.
 */
final readonly class PlayHoroscope
{
    public function __construct(
        private CalculateHoroscope $calculateHoroscope,
        private SpendAcorns $spendAcorns,
    ) {}

    /**
     * @throws InsufficientAcornsException
     */
    public function __invoke(StudentScheduleCookie $viewer, ZodiacSign $sign): ArcadePlay
    {
        return DB::transaction(function () use ($viewer, $sign): ArcadePlay {
            DB::table('student_schedules')->where('id', $viewer->id)->lockForUpdate()->value('id');

            $today = AcornExpiry::localDay(Date::now());

            $existing = ArcadePlay::query()
                ->where('student_schedule_id', $viewer->id)
                ->where('game', ArcadeGame::Horoscope)
                ->where('played_on', $today->toDateString())
                ->where('choice', $sign->value)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $play = new ArcadePlay;
            $play->student_schedule_id = $viewer->id;
            $play->game = ArcadeGame::Horoscope;
            $play->choice = $sign->value;
            $play->played_on = $today->toDateString();
            $play->result = ($this->calculateHoroscope)($sign, $today)->toArray();
            $play->saveOrFail();

            $price = (int) config('cafe.arcade.horoscope.price');

            $spend = ($this->spendAcorns)($viewer->id, $price, AcornTransactionKind::ArcadePlay, $play, [
                'game' => ArcadeGame::Horoscope->value,
                'sign' => $sign->value,
            ]);

            $play->acorn_transaction_id = $spend->id;
            $play->saveOrFail();

            return $play;
        });
    }
}
