<?php

declare(strict_types=1);

use App\Enums\DiscountStoreStatus;
use App\Enums\NewsletterIssueStatus;
use App\Enums\UserRole;
use App\Filament\Widgets\NeedsAttentionStats;
use App\Models\DiscountStore;
use App\Models\DiscountStoreComment;
use App\Models\DiscountStoreReport;
use App\Models\NewsletterIssue;
use App\Models\User;
use Livewire\Livewire;
use NouTools\Domains\Shared\Actions\ResolveModerationQueueCounts;

use function Pest\Laravel\actingAs;

it('counts items waiting for moderation', function (): void {
    DiscountStore::factory()->count(2)->create(['status' => DiscountStoreStatus::Pending]);
    $online = DiscountStore::factory()->create(['status' => DiscountStoreStatus::Online]);
    DiscountStoreComment::factory()->for($online, 'store')->create(['is_approved' => false]);
    DiscountStoreComment::factory()->for($online, 'store')->create(['is_approved' => true]);
    DiscountStoreReport::factory()->for($online, 'store')->create(['is_valid' => false]);
    NewsletterIssue::factory()->create(['status' => NewsletterIssueStatus::Ready]);
    NewsletterIssue::factory()->create(['status' => NewsletterIssueStatus::Draft]);

    expect(app(ResolveModerationQueueCounts::class)())->toBe([
        'pendingStores' => 2,
        'unapprovedComments' => 1,
        'invalidReports' => 1,
        'newsletterAwaitingPublish' => 1,
    ]);
});

it('shows the moderation widget to admins only', function (): void {
    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
    expect(NeedsAttentionStats::canView())->toBeTrue();
    Livewire::test(NeedsAttentionStats::class)->assertSee('待審核店家');

    auth()->logout();
    actingAs(User::factory()->create());
    expect(NeedsAttentionStats::canView())->toBeFalse();
});
