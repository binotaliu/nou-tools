<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Widgets\ScheduleOverviewStats;
use App\Models\StudentSchedule;
use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use NouTools\Domains\Shared\Actions\ResolveScheduleMetrics;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('shows the schedule widget on the dashboard', function (): void {
    $this->get('/admin')->assertSuccessful();

    Livewire::test(Dashboard::class)->assertSeeLivewire(ScheduleOverviewStats::class);
});

it('handles an empty database without dividing by zero', function (): void {
    $metrics = app(ResolveScheduleMetrics::class)();

    expect($metrics['total'])->toBe(0)
        ->and($metrics['reminderRate'])->toBe(0.0)
        ->and($metrics['dailyNew'])->toHaveCount(30);
});

it('computes adoption rates and daily buckets', function (): void {
    StudentSchedule::factory()->count(3)->create(['notify_on_class_start' => false]);
    StudentSchedule::factory()->create(['notify_on_class_start' => true, 'last_calendar_sync_at' => now()]);
    StudentSchedule::factory()->create(['created_at' => now()->subDays(20)]);

    $metrics = app(ResolveScheduleMetrics::class)();

    expect($metrics['total'])->toBe(5)
        ->and($metrics['newThisWeek'])->toBe(4)
        ->and($metrics['reminderRate'])->toBe(20.0)
        ->and($metrics['calendarSyncRate'])->toBe(20.0)
        ->and(array_sum($metrics['dailyNew']))->toBe(5);
});

it('buckets days in Taipei time', function (): void {
    // 20:00 UTC on the 1st is already 04:00 on the 2nd in Taipei.
    $this->travelTo(Date::parse('2026-09-10 12:00:00', 'UTC'));
    StudentSchedule::factory()->create(['created_at' => Date::parse('2026-09-01 20:00:00', 'UTC')]);

    $daily = app(ResolveScheduleMetrics::class)()['dailyNew'];

    expect($daily['2026-09-02'])->toBe(1)->and($daily['2026-09-01'])->toBe(0);
});

it('renders the stats widget', function (): void {
    StudentSchedule::factory()->count(2)->create();

    Livewire::test(ScheduleOverviewStats::class)->assertSee('課表總數');
});
