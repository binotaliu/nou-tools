<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Widgets\NewSchedulesChart;
use App\Filament\Widgets\PushDeliveryChart;
use App\Models\PushNotificationDelivery;
use App\Models\StudentSchedule;
use App\Models\User;
use Livewire\Livewire;
use NouTools\Domains\Shared\Actions\ResolvePushDeliveryMetrics;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

function pushDelivery(bool $success, ?string $reason = null, ?DateTimeInterface $at = null): void
{
    (new PushNotificationDelivery)->forceFill([
        'subscribable_type' => 'schedule',
        'subscribable_id' => '1',
        'endpoint' => 'https://push.example/'.fake()->uuid(),
        'success' => $success,
        'reason' => $reason,
        'created_at' => $at ?? now(),
    ])->save();
}

it('summarises recent push deliveries and ranks failure reasons', function (): void {
    pushDelivery(true);
    pushDelivery(false, 'Gone');
    pushDelivery(false, 'Gone');
    pushDelivery(false);
    pushDelivery(false, 'Gone', now()->subDays(10));

    expect(app(ResolvePushDeliveryMetrics::class)())->toBe([
        'succeeded' => 1,
        'failed' => 3,
        'failureReasons' => ['Gone' => 2, '未知原因' => 1],
    ]);
});

it('renders the chart widgets', function (): void {
    StudentSchedule::factory()->create();
    pushDelivery(false, 'Gone');

    Livewire::test(NewSchedulesChart::class)->assertSee('每日新增課表');
    Livewire::test(PushDeliveryChart::class)->assertSee('推播送達')->assertSee('Gone');
});

it('renders the push chart with no deliveries', function (): void {
    Livewire::test(PushDeliveryChart::class)->assertSee('沒有失敗紀錄');
});
