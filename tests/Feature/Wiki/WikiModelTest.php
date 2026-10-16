<?php

declare(strict_types=1);

use App\Models\WikiPage;
use App\Models\WikiSlugRedirect;

it('creates a live page with a current revision', function (): void {
    $page = WikiPage::factory()->live(['title' => '空大考試要去哪裡考？'])->create();

    expect($page->fresh()->currentRevision->title)->toBe('空大考試要去哪裡考？')
        ->and(WikiPage::query()->live()->whereKey($page->id)->exists())->toBeTrue();
});

it('validates slugs', function (string $slug, bool $valid): void {
    expect(WikiPage::isValidSlug($slug))->toBe($valid);
})->with([
    ['nou-exams-info', true],
    ['about-nou', true],
    ['exam2026', true],
    ['Nou-Exams', false],
    ['nou--exams', false],
    ['-nou', false],
    ['空大考試', false],
    ['new', false],
    ['edit-requests', false],
    [str_repeat('a', 61), false],
]);

it('treats slugs of pages and redirects as taken', function (): void {
    $page = WikiPage::factory()->create(['slug' => 'nou-exams-info']);
    $redirect = new WikiSlugRedirect;
    $redirect->old_slug = 'exams';
    $redirect->wiki_page_id = $page->id;
    $redirect->save();

    expect(WikiPage::isSlugAvailable('nou-exams-info'))->toBeFalse()
        ->and(WikiPage::isSlugAvailable('exams'))->toBeFalse()
        ->and(WikiPage::isSlugAvailable('nou-credits'))->toBeTrue();
});
