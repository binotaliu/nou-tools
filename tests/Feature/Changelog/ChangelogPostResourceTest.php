<?php

use App\Enums\ChangelogPostStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ChangelogPosts\Pages\CreateChangelogPost;
use App\Filament\Resources\ChangelogPosts\Pages\EditChangelogPost;
use App\Filament\Resources\ChangelogPosts\Pages\ListChangelogPosts;
use App\Models\ChangelogPost;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('is admin only', function () {
    actingAs(User::factory()->create());

    get('/admin/changelog-posts')->assertForbidden();
});

it('lists posts', function () {
    $posts = ChangelogPost::factory()->count(2)->create();

    Livewire::test(ListChangelogPosts::class)->assertCanSeeTableRecords($posts);
});

it('creates a post', function () {
    Livewire::test(CreateChangelogPost::class)
        ->fillForm(['title' => '新功能上線', 'slug' => 'new-feature-launch', 'body' => '內容'])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = ChangelogPost::query()->where('title', '新功能上線')->sole();

    expect($post->slug)->toBe('new-feature-launch')
        ->and($post->status)->toBe(ChangelogPostStatus::Draft);
});

it('rejects a slug with characters that would break the URL', function () {
    Livewire::test(CreateChangelogPost::class)
        ->fillForm(['title' => '新功能上線', 'slug' => '新功能上線', 'body' => '內容'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('publishes a post', function () {
    $post = ChangelogPost::factory()->create();

    Livewire::test(EditChangelogPost::class, ['record' => $post->getRouteKey()])
        ->callAction('publishNow')
        ->assertNotified();

    expect($post->refresh()->status)->toBe(ChangelogPostStatus::Published)
        ->and($post->published_at)->not->toBeNull();

    Livewire::test(EditChangelogPost::class, ['record' => $post->getRouteKey()])
        ->assertActionHidden('publishNow');
});

it('reports a post it cannot publish because it has no body', function () {
    $post = ChangelogPost::factory()->create(['body' => null]);

    Livewire::test(EditChangelogPost::class, ['record' => $post->getRouteKey()])
        ->callAction('publishNow')
        ->assertNotified();

    expect($post->refresh()->status)->toBe(ChangelogPostStatus::Draft);
});
