<?php

use App\Models\Announcement;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

/**
 * Selected source => categories directly from the Inertia
 * `viewModel.sourceCategorySelections` prop (this replaces scraping the
 * old server-rendered "目前條件" summary now that the filter chips are
 * rendered client-side by Vue rather than Blade).
 *
 * @return array<string, array<int, string>>
 */
$selectedSourceCategoriesFromResponse = function (TestResponse $response): array {
    $props = null;

    $response->assertInertia(function (Assert $page) use (&$props) {
        $props = $page->toArray()['props'];
    });

    return collect($props['viewModel']['sourceCategorySelections'])
        ->filter(fn (array $selection) => $selection['selectedCategories'] !== [])
        ->mapWithKeys(fn (array $selection) => [$selection['source'] => $selection['selectedCategories']])
        ->all();
};

it('shows announcement entry points on home page', function () {
    $response = get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->component('Home/Index'));
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
    $response->assertInertia(function (Assert $page) {
        $page->component('Announcements/Index');

        $titles = collect($page->toArray()['props']['viewModel']['announcements']['data'])->pluck('title');

        expect($titles)->toContain('期中考公告', '迎新活動');
    });
});

it('filters announcements by source and category', function () use ($selectedSourceCategoriesFromResponse) {
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

    $titles = null;

    $response->assertInertia(function (Assert $page) use (&$titles) {
        $titles = collect($page->toArray()['props']['viewModel']['announcements']['data'])->pluck('title');
    });

    expect($titles)->toContain('保留的公告');
    expect($titles)->not->toContain('錯誤分類公告', '錯誤來源公告');

    $currentFilters = $selectedSourceCategoriesFromResponse($response);

    expect($currentFilters)->toHaveKey('教務處');
    expect($currentFilters['教務處'])->toContain('考試資訊');
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
    $pageTwoResponse->assertInertia(function (Assert $page) {
        $viewModel = $page->toArray()['props']['viewModel'];

        expect(collect($viewModel['announcements']['data'])->pluck('title'))->not->toContain('其他來源公告');
        expect($viewModel['announcements']['current_page'])->toBe(2);
        expect($viewModel['announcements']['last_page'])->toBe(2);
    });
});

it('filters announcements by selected source categories tree', function () use ($selectedSourceCategoriesFromResponse) {
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

    $titles = null;

    $response->assertInertia(function (Assert $page) use (&$titles) {
        $titles = collect($page->toArray()['props']['viewModel']['announcements']['data'])->pluck('title');
    });

    expect($titles)->toContain('教務處考試公告');
    expect($titles)->not->toContain('教務處選課公告', '台北中心公告');

    $currentFilters = $selectedSourceCategoriesFromResponse($response);

    expect($currentFilters)->toHaveKey('教務處');
    expect($currentFilters['教務處'])->toContain('考試資訊');
});

it('filters announcements by multiple sources', function () use ($selectedSourceCategoriesFromResponse) {
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

    $titles = null;

    $response->assertInertia(function (Assert $page) use (&$titles) {
        $titles = collect($page->toArray()['props']['viewModel']['announcements']['data'])->pluck('title');
    });

    expect($titles)->toContain('教務處公告', '台北中心公告');
    expect($titles)->not->toContain('其他來源公告');

    $currentFilters = $selectedSourceCategoriesFromResponse($response);

    expect($currentFilters)->toHaveKeys(['教務處', '台北中心']);
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

    $titles = null;
    $sourceCategorySelections = null;

    $response->assertInertia(function (Assert $page) use (&$titles, &$sourceCategorySelections) {
        $viewModel = $page->toArray()['props']['viewModel'];
        $titles = collect($viewModel['announcements']['data'])->pluck('title');
        $sourceCategorySelections = collect($viewModel['sourceCategorySelections'])->keyBy('source');
    });

    expect($titles)->toContain('台北中心教務公告');

    // The frontend (useAnnouncementFilter/Announcements/Index.vue) collapses
    // the displayed chips to just the source name when every available
    // category under it is selected; at the data level that's simply
    // selectedCategories === availableCategories.
    $selection = $sourceCategorySelections->get('台北中心');

    expect($selection)->not->toBeNull();
    expect(collect($selection['selectedCategories'])->sort()->values()->all())
        ->toBe($allCategoriesForTaipeiCenter->sort()->values()->all());
    expect(collect($selection['availableCategories'])->sort()->values()->all())
        ->toBe($allCategoriesForTaipeiCenter->sort()->values()->all());
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
    $response->assertSee('第 1 / 1 頁（共 1 筆）');
});

it('displays pagination links in the markdown page when there are multiple pages', function () {
    Announcement::factory()->count(35)->create();

    $response = get(route('announcements.index.md'));

    $response->assertSuccessful();
    $response->assertSee('第 1 / 2 頁（共 35 筆）');
    $response->assertSee('下一頁：');
    $response->assertDontSee('上一頁：');

    $pageTwoResponse = get(route('announcements.index.md', ['page' => 2]));

    $pageTwoResponse->assertSuccessful();
    $pageTwoResponse->assertSee('第 2 / 2 頁（共 35 筆）');
    $pageTwoResponse->assertSee('上一頁：');
    $pageTwoResponse->assertDontSee('下一頁：');
});

it('describes active filters in the markdown page', function () {
    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '教務處考試公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '台北中心',
        'category' => '學務訊息',
        'title' => '台北中心公告',
    ]);

    $response = get(route('announcements.index.md', [
        'source_categories' => [
            '教務處' => ['考試資訊'],
        ],
    ]));

    $response->assertSuccessful();
    $response->assertSee('教務處考試公告');
    $response->assertDontSee('台北中心公告');
    $response->assertSee('目前篩選來源：教務處');
    $response->assertSee('已篩選分類：考試資訊');
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

it('tags each source selection with its group, ordered like the schedule preferences', function () {
    config()->set('announcements.sources', [
        ['name' => '人文學系', 'category' => '最新消息', 'is_active' => true],
        ['name' => '台北中心', 'category' => '最新消息', 'is_active' => true],
        ['name' => '教務處', 'category' => '考試資訊', 'is_active' => true],
    ]);
    config()->set('announcements.source_groups', [
        '人文學系' => 'department',
        '台北中心' => 'center',
    ]);

    get(route('announcements.index'))->assertInertia(function (Assert $page) {
        $selections = collect($page->toArray()['props']['viewModel']['sourceCategorySelections']);

        expect($selections->pluck('source')->all())->toBe(['教務處', '台北中心', '人文學系'])
            ->and($selections->pluck('group')->all())->toBe(['administrative', 'center', 'department'])
            ->and($selections->pluck('groupLabel')->all())->toBe(['各處室', '學習指導中心', '學系']);
    });
});
