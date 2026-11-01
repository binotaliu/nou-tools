<?php

use App\Enums\AcornTransactionKind;
use App\Enums\ArcadeGame;
use App\Models\AcornTransaction;
use App\Models\ArcadePlay;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use Illuminate\Support\Carbon;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;

$arcadeCookie = function (StudentSchedule $schedule): string {
    return json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid, 'name' => $schedule->name]);
};

$player = function (int $acorns = 10): StudentSchedule {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '玩家同學']);
    AcornTransaction::factory()->for($schedule, 'schedule')->credit($acorns)->create();

    return $schedule;
};

it('charges the price and stores the reading when a coin goes in', function () use ($arcadeCookie, $player) {
    $schedule = $player(10);

    $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
        ->postJson(route('cafe.arcade.horoscope.store'), ['sign' => 'leo'])
        ->assertOk()
        ->assertJsonPath('reading.sign', 'leo')
        ->assertJsonPath('reading.signLabel', '獅子座')
        ->assertJsonPath('reading.scores.0.key', 'overall')
        ->assertJsonCount(5, 'reading.scores')
        ->assertJsonCount(2, 'reading.advice')
        ->assertJsonPath('acorns.balance', 8);

    $play = ArcadePlay::query()->firstOrFail();
    expect($play->game)->toBe(ArcadeGame::Horoscope)
        ->and($play->choice)->toBe('leo')
        ->and($play->acorn_transaction_id)->not->toBeNull()
        ->and(AcornTransaction::query()->where('kind', AcornTransactionKind::ArcadePlay)->value('amount'))->toBe(-2);
});

it('shows the same reading again for the same sign on the same day without charging', function () use ($arcadeCookie, $player) {
    $schedule = $player(10);
    $request = fn () => $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
        ->postJson(route('cafe.arcade.horoscope.store'), ['sign' => 'aries']);

    $first = $request()->assertOk()->json('reading');
    $second = $request()->assertOk()->assertJsonPath('acorns.balance', 8)->json('reading');

    expect($second)->toBe($first)
        ->and(ArcadePlay::query()->count())->toBe(1)
        ->and(AcornTransaction::query()->where('kind', AcornTransactionKind::ArcadePlay)->count())->toBe(1);
});

it('charges again for another sign', function () use ($arcadeCookie, $player) {
    $schedule = $player(10);

    foreach (['aries', 'taurus'] as $sign) {
        $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
            ->postJson(route('cafe.arcade.horoscope.store'), ['sign' => $sign])
            ->assertOk();
    }

    expect(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(6)
        ->and(ArcadePlay::query()->count())->toBe(2);
});

it('charges again after the Taipei day rolls over', function () use ($arcadeCookie, $player) {
    $schedule = $player(10);
    $this->travelTo(Carbon::parse('2026-11-03 20:00', 'Asia/Taipei'));

    $request = fn () => $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
        ->postJson(route('cafe.arcade.horoscope.store'), ['sign' => 'virgo']);

    $request()->assertOk();
    $this->travelTo(Carbon::parse('2026-11-04 00:30', 'Asia/Taipei'));
    $request()->assertOk()->assertJsonPath('acorns.balance', 6);

    expect(ArcadePlay::query()->pluck('played_on')->sort()->values()->all())->toBe(['2026-11-03', '2026-11-04']);
});

it('turns the player away without enough acorns', function () use ($arcadeCookie, $player) {
    $schedule = $player(1);

    $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
        ->postJson(route('cafe.arcade.horoscope.store'), ['sign' => 'libra'])
        ->assertStatus(422)
        ->assertJsonPath('message', '橡實不夠囉。');

    expect(ArcadePlay::query()->count())->toBe(0)
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(1);
});

it('requires a signed-in schedule with a nickname', function () use ($arcadeCookie) {
    $this->postJson(route('cafe.arcade.horoscope.store'), ['sign' => 'leo'])->assertForbidden();

    $schedule = StudentSchedule::factory()->create();
    AcornTransaction::factory()->for($schedule, 'schedule')->credit(10)->create();

    $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
        ->postJson(route('cafe.arcade.horoscope.store'), ['sign' => 'leo'])
        ->assertForbidden();
});

it('rejects an unknown sign', function () use ($arcadeCookie, $player) {
    $schedule = $player(10);

    $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
        ->postJson(route('cafe.arcade.horoscope.store'), ['sign' => 'ophiuchus'])
        ->assertUnprocessable();
});

it('lists the signs, the price and only the readings unlocked today', function () use ($arcadeCookie, $player) {
    $this->getJson(route('cafe.arcade.horoscope.show'))
        ->assertOk()
        ->assertJsonCount(0, 'machine.unlocked');

    $schedule = $player(10);
    ArcadePlay::factory()->for($schedule, 'schedule')->create(['choice' => 'pisces', 'played_on' => '2020-01-01']);

    $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
        ->postJson(route('cafe.arcade.horoscope.store'), ['sign' => 'gemini'])
        ->assertOk();

    $this->withCredentials()->withCookie('student_schedule', $arcadeCookie($schedule))
        ->getJson(route('cafe.arcade.horoscope.show'))
        ->assertOk()
        ->assertJsonPath('machine.price', 2)
        ->assertJsonCount(12, 'machine.signs')
        ->assertJsonCount(1, 'machine.unlocked')
        ->assertJsonPath('machine.unlocked.0.sign', 'gemini');
});
