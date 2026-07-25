<?php

use App\Enums\DiscountStoreStatus;
use App\Models\Course;
use App\Models\DiscountStore;
use App\Models\StudentSchedule;
use Illuminate\Support\Str;

test('sitemap returns xml with static and dynamic pages', function () {
    $course = Course::factory()->create();

    $response = $this->get(route('sitemap'));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertSee(route('home'), false)
        ->assertSee(route('announcements.index'), false)
        ->assertSee(route('discount-stores.index'), false)
        ->assertSee(route('course.show', $course), false)
        ->assertSee(route('articles.index', 'kb'), false)
        ->assertSee(route('articles.show', ['kb', 'about-nou']), false);
});

test('sitemap does not include noindexed schedule or individual store pages', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'My Schedule',
    ]);
    $store = DiscountStore::factory()->create(['status' => DiscountStoreStatus::Online]);

    $response = $this->get(route('sitemap'));

    $response->assertStatus(200)
        ->assertDontSee(route('schedules.show', $schedule), false)
        ->assertDontSee(route('discount-stores.show', $store), false);
});
