<?php

use App\Enums\ArticleType;
use App\Enums\DiscountStoreStatus;
use App\Models\Course;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use App\Models\StudentSchedule;
use Illuminate\Support\Str;

// Inertia's <Head> only runs in the browser, so crawlers depend on these tags
// being in the server-rendered HTML (resources/views/open-graph/).

it('server-renders title, description and Open Graph tags on static pages', function (string $routeName, string $title) {
    $this->get(route($routeName))
        ->assertSuccessful()
        ->assertSee('<title>'.$title.'</title>', false)
        ->assertSee('<meta data-seo name="description"', false)
        ->assertSee('<meta data-seo property="og:title" content="'.$title.'" />', false)
        ->assertSee('<meta data-seo property="og:description"', false)
        ->assertSee('<meta data-seo property="og:url" content="'.route($routeName).'" />', false)
        ->assertSee('<meta data-seo name="twitter:card"', false)
        ->assertSee('<meta property="og:image" content="'.asset('og-image.png').'" />', false)
        ->assertDontSee('name="robots"', false);
})->with([
    'home' => ['home', 'NOU 小幫手'],
    'announcements' => ['announcements.index', '學校公告 - NOU 小幫手'],
    'directory' => ['directory.index', '連結 / 學習指導中心目錄 - NOU 小幫手'],
    'course schedule' => ['course.schedule', '本學期開課表 - NOU 小幫手'],
    'about' => ['about', '關於本站 - NOU 小幫手'],
    'pwa install' => ['pwa.install', '安裝成 App - NOU 小幫手'],
    'study room' => ['study-room.show', '自習室 - NOU 小幫手'],
    'new discount store' => ['discount-stores.create', '新增優惠店家 - NOU 小幫手'],
]);

it('advertises a generated og:image card on the Alt UU page', function () {
    $this->get(route('alt-uu'))
        ->assertSuccessful()
        ->assertSee('<title>Alt UU - NOU 小幫手</title>', false)
        ->assertSee('<template data-og-image', false)
        ->assertSee('給 NOU 同學的 UU 平台瀏覽器 App', false)
        ->assertSee('<meta property="og:image" content="'.url('/og-image/'), false)
        ->assertDontSee(asset('og-image.png'), false);
});

it('server-renders the newsletter index with its Atom feed link', function () {
    $this->get(route('newsletter.index'))
        ->assertSuccessful()
        ->assertSee('<title>浣熊的空大雙週報 - NOU 小幫手</title>', false)
        ->assertSee('type="application/atom+xml"', false)
        ->assertSee('href="'.route('newsletter.feed').'"', false)
        ->assertSee('"@type":"ItemList"', false);
});

it('server-renders WebSite JSON-LD on the home page', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('<script data-seo type="application/ld+json">', false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertSee('"url":"'.url('/').'"', false);
});

it('server-renders an article page with its own description and JSON-LD', function () {
    $response = $this->get(route('articles.show', ['type' => ArticleType::MANUAL->value, 'slug' => 'welcome']))
        ->assertSuccessful();

    $article = $response->inertiaProps('viewModel.article');

    $response
        ->assertSee('<title>'.e($article['title']).' - 操作手冊 - NOU 小幫手</title>', false)
        ->assertSee('<meta data-seo property="og:type" content="article" />', false)
        ->assertSee('<meta data-seo property="og:title" content="'.e($article['title']).'" />', false)
        ->assertSee('<meta data-seo name="description" content="'.e($article['description']).'" />', false)
        ->assertSee('property="article:published_time"', false)
        ->assertSee('"@type":"Article"', false);
});

it('server-renders an article index page', function () {
    $this->get(route('articles.index', ['type' => ArticleType::KNOWLEDGE_BASE->value]))
        ->assertSuccessful()
        ->assertSee('<title>知識庫 - NOU 小幫手</title>', false)
        ->assertSee('"@type":"CollectionPage"', false);
});

it('server-renders a course page with a generated description', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'credits' => 3,
        'department' => 'Test Department',
        'term' => '11401',
    ]);

    $description = 'Test Course 是國立空中大學 Test Department 在 '.Str::toSemesterDisplay('11401').' 開設的 3 學分課程';

    $this->get(route('course.show', $course))
        ->assertSuccessful()
        ->assertSee('<title>Test Course - 檢視課程 - NOU 小幫手</title>', false)
        ->assertSee('<meta data-seo name="description" content="'.$description.'" />', false)
        ->assertSee('"@type":"Course"', false);
});

it('server-renders a discount store page with its SEO description', function () {
    $store = DiscountStore::factory()
        ->for(DiscountStoreCategory::factory(), 'category')
        ->create(['name' => '測試優惠店家', 'status' => DiscountStoreStatus::Online]);

    $response = $this->get(route('discount-stores.show', $store))->assertSuccessful();

    $response
        ->assertSee('<title>測試優惠店家 - 優惠店家 - NOU 小幫手</title>', false)
        ->assertSee('<meta data-seo name="description" content="'.e($response->inertiaProps('viewModel.seoDescription')).'" />', false);
});

it('marks personal schedule pages noindex in the server-rendered head', function () {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '我的期末課表']);

    $this->get(route('schedules.show', $schedule))
        ->assertSuccessful()
        ->assertSee('<title>我的期末課表 - NOU 小幫手</title>', false)
        ->assertSee('<meta data-seo name="robots" content="noindex, nofollow" />', false);

    $this->get(route('schedules.customize', $schedule))
        ->assertSuccessful()
        ->assertSee('<meta data-seo name="robots" content="noindex, nofollow" />', false);

    $this->get(route('schedules.create'))
        ->assertSuccessful()
        ->assertSee('<title>新增課表 - NOU 小幫手</title>', false)
        ->assertSee('<meta data-seo name="robots" content="noindex, nofollow" />', false);
});

it('does not tag pages without an open-graph view', function () {
    $this->get(route('offline'))->assertDontSee('data-seo', false);
});

it('leaves the head untagged when a matched route renders the Error page', function () {
    $this->get(route('articles.show', ['type' => ArticleType::MANUAL->value, 'slug' => 'no-such-article']))
        ->assertNotFound()
        ->assertDontSee('data-seo', false);
});

it('advertises a generated og:image card on a course page', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'credits' => 3,
        'department' => 'Test Department',
        'term' => '11401',
        'media' => '網頁',
    ]);

    $this->get(route('course.show', $course))
        ->assertSuccessful()
        ->assertSee('<template data-og-image', false)
        ->assertSee('Test Course', false)
        ->assertSee('空中大學Test Department課程', false)
        ->assertSee('課程・'.Str::toSemesterDisplay('11401'), false)
        ->assertSee('3 學分・網頁課程', false)
        ->assertSee('/images/plus.svg', false)
        ->assertSee('<meta property="og:image" content="'.url('/og-image/'), false)
        ->assertDontSee(asset('og-image.png'), false);
});

it('ships the recoloured plus pattern the course card uses as its background', function () {
    $svg = file_get_contents(public_path('images/plus.svg'));

    expect($svg)
        ->toContain('fill="#fff"')
        ->not->toContain('#000')
        ->toContain('Steve Schoger')
        ->toContain('CC BY 4.0');
});

it('advertises a generated og:image card on an article page', function () {
    $response = $this->get(route('articles.show', ['type' => ArticleType::MANUAL->value, 'slug' => 'welcome']))
        ->assertSuccessful();

    $article = $response->inertiaProps('viewModel.article');

    $response
        ->assertSee('<template data-og-image', false)
        ->assertSee(e($article['title']), false)
        ->assertSee('NOU 小幫手操作手冊', false)
        ->assertSee('/images/plus.svg', false)
        ->assertSee('<meta property="og:image" content="'.url('/og-image/'), false)
        ->assertDontSee(asset('og-image.png'), false);
});

it('advertises a generated og:image card on an article index page', function () {
    $this->get(route('articles.index', ['type' => ArticleType::KNOWLEDGE_BASE->value]))
        ->assertSuccessful()
        ->assertSee('<template data-og-image', false)
        ->assertSee('知識庫', false)
        ->assertSee('<meta property="og:image" content="'.url('/og-image/'), false)
        ->assertDontSee(asset('og-image.png'), false);
});
