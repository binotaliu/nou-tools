<?php

use App\Enums\DiscountStoreStatus;
use App\Models\Course;
use App\Models\DiscountStore;
use App\Models\StudentSchedule;
use App\Models\WikiCategory;
use App\Models\WikiPage;
use Illuminate\Support\Str;

test('sitemap returns xml with static and dynamic pages', function () {
    $course = Course::factory()->create();

    $response = $this->get(route('sitemap'));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertSee(route('home'), false)
        ->assertSee(route('announcements.index'), false)
        ->assertSee(route('video-classes.index'), false)
        ->assertSee(route('discount-stores.index'), false)
        ->assertSee(route('course.show', $course), false)
        ->assertSee(route('articles.index', 'manual'), false)
        ->assertSee(route('articles.show', ['manual', 'welcome']), false)
        ->assertSee(route('wiki.index'), false)
        ->assertSee(route('wiki.show', 'about-nou'), false)
        ->assertSee(route('wiki.all'), false);
});

test('sitemap lists wiki categories that have a listed page', function () {
    $page = WikiPage::factory()->live(['categories' => ['考試']])->create(['slug' => 'exam-page']);
    $category = WikiCategory::query()->forceCreate(['name' => '考試']);
    $category->pages()->attach($page);
    $empty = WikiCategory::query()->forceCreate(['name' => '空分類']);

    $this->get(route('sitemap'))
        ->assertSee(route('wiki.category', $category), false)
        ->assertDontSee(route('wiki.category', $empty), false);
});

test('sitemap does not include noindexed schedule pages but does include online store pages', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'My Schedule',
    ]);
    $onlineStore = DiscountStore::factory()->create(['status' => DiscountStoreStatus::Online]);
    $pendingStore = DiscountStore::factory()->create(['status' => DiscountStoreStatus::Pending]);

    $response = $this->get(route('sitemap'));

    $response->assertStatus(200)
        ->assertDontSee(route('schedules.show', $schedule), false)
        ->assertSee(route('discount-stores.show', $onlineStore), false)
        ->assertDontSee(route('discount-stores.show', $pendingStore), false);
});

test('llms.txt returns markdown with key links', function () {
    $response = $this->get('/llms.txt');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# NOU 小幫手', false)
        ->assertSee(route('home'), false)
        ->assertSee(route('discount-stores.index'), false)
        ->assertSee(route('discount-stores.index.md'), false)
        ->assertSee(route('video-classes.index.md'), false);
});
