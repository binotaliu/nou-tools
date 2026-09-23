<?php

use App\Enums\UserRole;
use App\Models\ChangelogPost;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('highlights the latest published post and lists the rest as past posts', function () {
    ChangelogPost::factory()->published()->create(['slug' => 'first', 'published_at' => now()->subDays(2)]);
    ChangelogPost::factory()->published()->create(['slug' => 'second', 'published_at' => now()->subDay()]);
    ChangelogPost::factory()->create(['slug' => 'draft']);

    get(route('changelog.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Changelog/Index')
            ->where('viewModel.latestPost.slug', 'second')
            ->where('viewModel.posts.data.0.slug', 'first')
            ->has('viewModel.posts.data', 1));
});

it('renders a post with server-rendered markdown and no XSS', function () {
    ChangelogPost::factory()->published()->create([
        'slug' => 'new-feature',
        'title' => '新功能上線',
        'body' => '我們新增了 **重要功能**。<script>alert(1)</script>',
    ]);

    get('/changelog/new-feature')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Changelog/Show')
            ->where('viewModel.post.slug', 'new-feature')
            ->where('viewModel.post.isPublished', true)
            ->where('viewModel.post.bodyHtml', fn (string $html) => str_contains($html, '<strong>重要功能</strong>')
                && ! str_contains($html, '<script>')));
});

it('hides unpublished posts from guests but lets admins preview them', function () {
    ChangelogPost::factory()->create(['slug' => 'draft-post']);

    get('/changelog/draft-post')->assertNotFound();

    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));

    get('/changelog/draft-post')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.post.isPublished', false)
            ->where('viewModel.post.statusLabel', '草稿'));
});

it('does not let non-admin users preview drafts', function () {
    ChangelogPost::factory()->create(['slug' => 'draft-post']);

    actingAs(User::factory()->create());

    get('/changelog/draft-post')->assertNotFound();
});

it('returns 404 for an unknown slug', function () {
    get('/changelog/unknown')->assertNotFound();
});

it('links to the previous and next published post', function () {
    ChangelogPost::factory()->published()->create(['slug' => 'first', 'published_at' => now()->subDays(2)]);
    $current = ChangelogPost::factory()->published()->create(['slug' => 'second', 'published_at' => now()->subDay()]);
    ChangelogPost::factory()->published()->create(['slug' => 'third', 'published_at' => now()]);

    get(route('changelog.show', $current))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.previousPost.slug', 'first')
            ->where('viewModel.nextPost.slug', 'third'));
});
