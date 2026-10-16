<?php

declare(strict_types=1);

use App\Enums\WikiRevisionOrigin;
use App\Models\WikiPage;
use App\Models\WikiSlugRedirect;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;
use NouTools\Domains\Wiki\Actions\PublishWikiRevision;
use NouTools\Domains\Wiki\DataTransferObjects\PublishWikiRevisionDTO;

beforeEach(function (): void {
    // The page the old knowledge base was imported into.
    WikiPage::query()->where('slug', 'about-nou')->forceDelete();

    $this->exams = WikiPage::factory()->live([
        'title' => '空大考試要去哪裡考？',
        'description' => '考場、時間與注意事項。',
        'body' => "期中考與期末考都在週末舉行，請攜帶學生證。[^1]\n\n## 考場\n\n各學習指導中心。\n\n[^1]: [考試須知](https://www.nou.edu.tw/exam)\n",
        'categories' => ['考試'],
    ])->create(['slug' => 'nou-exams-info', 'created_at' => now()->subDays(2)]);
});

it('features a page marked 精選 and rotates through them by day', function (): void {
    $featured = WikiPage::factory()->featured()->live(['title' => '學分抵免', 'categories' => ['選課']])->create(['slug' => 'credit-transfer']);
    $other = WikiPage::factory()->featured()->live(['title' => '選課流程'])->create(['slug' => 'course-selection']);

    $slugOn = function (string $date): string {
        $this->travelTo(CarbonImmutable::parse($date.' 12:00', 'Asia/Taipei'));

        return $this->get(route('wiki.index'))->viewData('page')['props']['viewModel']['featured']['page']['slug'];
    };

    $today = $slugOn('2026-10-16');
    $tomorrow = $slugOn('2026-10-17');

    expect([$today, $tomorrow])->toEqualCanonicalizing([$featured->slug, $other->slug])
        ->and($slugOn('2026-10-18'))->toBe($today);
});

it('stands in any page while none is marked 精選, with a plain-text excerpt', function (): void {
    $this->get(route('wiki.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.featured.isFeatured', false)
            ->where('viewModel.featured.page.slug', 'nou-exams-info')
            ->where('viewModel.featured.excerpt', '期中考與期末考都在週末舉行，請攜帶學生證。')
            ->where('viewModel.featured.categories.0.name', '考試'));
});

it('lists new pages and red links worth writing, most linked first', function (): void {
    $publish = fn (string $slug, string $title, string $body) => app(PublishWikiRevision::class)(null, new PublishWikiRevisionDTO(
        title: $title,
        description: null,
        body: $body,
        categories: [],
        editSummary: '建立',
        origin: WikiRevisionOrigin::Admin,
    ), $slug);

    $publish('freshman-guide', '新生入門', '先看[[credit-transfer]]與[[course-selection]]，也看[[nou-exams-info]]。');
    $this->travel(1)->minute();
    $publish('transfer', '轉學', '見[[credit-transfer]]與[[old-name]]。');
    WikiSlugRedirect::query()->forceCreate(['old_slug' => 'old-name', 'wiki_page_id' => $this->exams->id]);

    $this->get(route('wiki.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.newPages.0.slug', 'transfer')
            ->has('viewModel.wantedPages', 2)
            ->where('viewModel.wantedPages.0.slug', 'credit-transfer')
            ->where('viewModel.wantedPages.0.linkCount', 2)
            ->where('viewModel.wantedPages.0.createUrl', route('wiki.new', ['slug' => 'credit-transfer']))
            ->where('viewModel.wantedPages.1.slug', 'course-selection'));
});

it('counts listed pages', function (): void {
    $this->get(route('wiki.index'))
        ->assertInertia(fn (Assert $page) => $page->where('viewModel.pageCount', 1));
});

it('lists every page on 所有條目 and in the markdown twin', function (): void {
    $this->get(route('wiki.all'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wiki/All')
            ->where('viewModel.pages.0.slug', 'nou-exams-info'));

    $this->get('/wiki.md')->assertOk()->assertSee('[空大考試要去哪裡考？]', false);
});

it('reserves the 所有條目 path', function (): void {
    expect(WikiPage::isValidSlug('all'))->toBeFalse();
});
