<?php

use App\Models\Announcement;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\get;

function currentFilterSummaryHtml(TestResponse $response): string
{
    preg_match(
        '/目前條件：<\/span>\s*<div[^>]*>(.*?)<\/div>\s*<\/div>/su',
        $response->getContent(),
        $matches,
    );

    expect($matches[1] ?? null)->not->toBeNull();

    return $matches[1];
}

function currentPaginationSummaryText(TestResponse $response): string
{
    preg_match(
        '/text-warm-600 dark:text-zinc-400">(.*?)<\/p>/su',
        $response->getContent(),
        $matches,
    );

    expect($matches[1] ?? null)->not->toBeNull();

    return preg_replace('/\s+/u', ' ', trim($matches[1]));
}

it('shows announcement entry points on home page', function () {
    $response = get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('檢視學校公告');
    $response->assertSee(route('announcements.index'));
});

it('shows latest announcements with filter options', function () {
    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '期中考公告',
        'published_at' => now()->subDay(),
    ]);

    Announcement::factory()->create([
        'source_name' => '學務處',
        'category' => '活動資訊',
        'title' => '迎新活動',
        'published_at' => now()->subHours(2),
    ]);

    $response = get(route('announcements.index'));

    $response->assertSuccessful();
    $response->assertSee('學校公告');
    $response->assertSee('期中考公告');
    $response->assertSee('迎新活動');
    $response->assertSee('教務處');
    $response->assertSee('活動資訊');
});

it('filters announcements by source and category', function () {
    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '保留的公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '註冊選課',
        'title' => '錯誤分類公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '學務處',
        'category' => '考試資訊',
        'title' => '錯誤來源公告',
    ]);

    $response = get(route('announcements.index', [
        'source' => ['教務處'],
        'category' => '考試資訊',
    ]));

    $response->assertSuccessful();
    $response->assertSee('保留的公告');
    $response->assertDontSee('錯誤分類公告');
    $response->assertDontSee('錯誤來源公告');

    $currentFilters = currentFilterSummaryHtml($response);

    expect($currentFilters)->toContain('教務處');
    expect($currentFilters)->toContain('考試資訊');
});

it('keeps filtered results paginated', function () {
    Announcement::factory()->count(31)->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
    ]);

    Announcement::factory()->create([
        'source_name' => '學務處',
        'category' => '活動資訊',
        'title' => '其他來源公告',
    ]);

    $pageTwoResponse = get(route('announcements.index', [
        'source' => ['教務處'],
        'category' => '考試資訊',
        'page' => 2,
    ]));

    $pageTwoResponse->assertSuccessful();
    $pageTwoResponse->assertDontSee('其他來源公告');

    $paginationSummary = currentPaginationSummaryText($pageTwoResponse);

    expect($paginationSummary)->toContain('第 2 / 2 頁，共');
});

it('filters announcements by selected source categories tree', function () {
    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '教務處考試公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '註冊選課',
        'title' => '教務處選課公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '台北中心',
        'category' => '學務訊息',
        'title' => '台北中心公告',
    ]);

    $response = get(route('announcements.index', [
        'source_categories' => [
            '教務處' => ['考試資訊'],
        ],
    ]));

    $response->assertSuccessful();
    $response->assertSee('教務處考試公告');
    $response->assertDontSee('教務處選課公告');
    $response->assertDontSee('台北中心公告');

    $currentFilters = currentFilterSummaryHtml($response);

    expect($currentFilters)->toContain('教務處');
    expect($currentFilters)->toContain('考試資訊');
});

it('filters announcements by multiple sources', function () {
    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '教務處公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '台北中心',
        'category' => '學務訊息',
        'title' => '台北中心公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '學務處',
        'category' => '活動資訊',
        'title' => '其他來源公告',
    ]);

    $response = get(route('announcements.index', [
        'source' => ['教務處', '台北中心'],
    ]));

    $response->assertSuccessful();
    $response->assertSee('教務處公告');
    $response->assertSee('台北中心公告');
    $response->assertDontSee('其他來源公告');

    $currentFilters = currentFilterSummaryHtml($response);

    expect($currentFilters)->toContain('教務處');
    expect($currentFilters)->toContain('台北中心');
});

it('shows only the source when all categories under it are selected', function () {
    Announcement::factory()->create([
        'source_name' => '台北中心',
        'category' => '教務訊息',
        'title' => '台北中心教務公告',
    ]);

    $allCategoriesForTaipeiCenter = collect(config('announcements.sources'))
        ->filter(function (array $source): bool {
            return ($source['is_active'] ?? false) && $source['name'] === '台北中心';
        })
        ->pluck('category')
        ->unique()
        ->values();

    expect($allCategoriesForTaipeiCenter)->not->toBeEmpty();

    $response = get(route('announcements.index', [
        'source_categories' => [
            '台北中心' => $allCategoriesForTaipeiCenter->all(),
        ],
    ]));

    $response->assertSuccessful();
    $response->assertSee('台北中心教務公告');

    $currentFilters = currentFilterSummaryHtml($response);

    expect($currentFilters)->toContain('台北中心');

    $allCategoriesForTaipeiCenter->each(function (string $category) use ($currentFilters): void {
        expect($currentFilters)->not->toContain($category);
    });
});

it('displays the announcement index markdown page', function () {
    $announcement = Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '期中考公告',
        'published_at' => now()->subDay(),
    ]);

    $response = get(route('announcements.index.md'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    $response->assertSee('# 學校公告', false);
    $response->assertSee('期中考公告');
    $response->assertSee($announcement->url, false);
});

it('returns markdown from the announcement index when the client prefers it in the Accept header', function () {
    $announcement = Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '期中考公告',
        'published_at' => now()->subDay(),
    ]);

    $response = get(route('announcements.index'), [
        'Accept' => 'text/markdown, text/html;q=0.8',
    ]);

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    $response->assertSee('# 學校公告', false);
    $response->assertSee($announcement->url, false);
});
