<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Widgets\StudyFocusChart;
use App\Filament\Widgets\StudyRoomStats;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use NouTools\Domains\Shared\Actions\ResolveStudyRoomMetrics;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('returns zeros for an empty room', function (): void {
    $metrics = app(ResolveStudyRoomMetrics::class)();

    expect($metrics['capacity'])->toBe(0)
        ->and($metrics['completionRate'])->toBe(0.0)
        ->and($metrics['dailyFocusHours'])->toHaveCount(14);
});

it('counts occupancy, focus hours and completion', function (): void {
    StudyRoomSeat::factory()->create();
    StudyRoomSeat::factory()->occupiedBy(StudentSchedule::factory()->create())->create();

    StudyRoomSession::factory()->create(['started_at' => now()->subMinutes(10), 'focus_seconds' => 3600, 'was_completed' => true]);
    StudyRoomSession::factory()->create(['started_at' => now()->subMinutes(5), 'focus_seconds' => 1800, 'was_completed' => false]);
    StudyRoomSession::factory()->create(['started_at' => now()->subDays(30), 'focus_seconds' => 7200]);

    $metrics = app(ResolveStudyRoomMetrics::class)();

    expect($metrics['occupied'])->toBe(1)
        ->and($metrics['capacity'])->toBe(2)
        ->and($metrics['focusHoursThisWeek'])->toBe(1.5)
        ->and($metrics['completionRate'])->toBe(50.0)
        ->and(array_sum($metrics['dailyFocusHours']))->toBe(1.5);
});

it('buckets sessions in Taipei time', function (): void {
    $this->travelTo(Date::parse('2026-09-10 12:00:00', 'UTC'));
    StudyRoomSession::factory()->create(['started_at' => Date::parse('2026-09-09 20:00:00', 'UTC'), 'focus_seconds' => 3600]);

    expect(app(ResolveStudyRoomMetrics::class)()['dailyFocusHours']['2026-09-10'])->toBe(1.0);
});

it('renders the study room widgets', function (): void {
    Livewire::test(StudyRoomStats::class)->assertSee('目前座位');
    Livewire::test(StudyFocusChart::class)->assertSee('每日專注時數');
});
