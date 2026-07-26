<?php

use App\Enums\ArticleType;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DiscountStoreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\Markdown\AnnouncementIndexMarkdownController;
use App\Http\Controllers\Markdown\ArticleIndexMarkdownController;
use App\Http\Controllers\Markdown\ArticleShowMarkdownController;
use App\Http\Controllers\Markdown\CourseShowMarkdownController;
use App\Http\Controllers\Markdown\DiscountStoreIndexMarkdownController;
use App\Http\Controllers\Markdown\DiscountStoreShowMarkdownController;
use App\Http\Controllers\Markdown\HomeIndexMarkdownController;
use App\Http\Controllers\Markdown\ScheduleShowMarkdownController;
use App\Http\Controllers\ScheduleCalendarController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::view('/docs/api', 'redocly')->name('docs.api.view');
Route::get('/docs/api.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/yaml; charset=utf-8',
    ]);
})->name('docs.api.yaml');

Route::get('/', [HomeController::class, 'index'])->name('home')
    ->withMarkdown(HomeIndexMarkdownController::class, uri: '/llms.txt', name: 'llms-txt');

Route::view('/alt-uu', 'alt-uu')->name('alt-uu');

Route::view('/offline', 'offline')->name('offline');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index')
    ->withMarkdown(AnnouncementIndexMarkdownController::class);

Route::get('/courses/{course}', [CourseController::class, 'show'])->name('course.show')
    ->withMarkdown(CourseShowMarkdownController::class);

Route::permanentRedirect('/schedule/create', '/schedules/create');
Route::permanentRedirect('/schedule/{schedule}', '/schedules/{schedule}');
Route::permanentRedirect('/schedule/{schedule}/edit', '/schedules/{schedule}/edit');
Route::permanentRedirect('/schedule/{schedule}/calendar', '/schedules/{schedule}/calendar');

Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
Route::get('/schedules/{schedule}', [ScheduleController::class, 'show'])->name('schedules.show')
    ->withMarkdown(ScheduleShowMarkdownController::class);
Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
Route::get('/schedules/{schedule}/customize', [ScheduleController::class, 'customize'])->name('schedules.customize');
Route::put('/schedules/{schedule}/customize', [ScheduleController::class, 'updateCustomization'])->name('schedules.customize.update');
Route::get('/schedules/{schedule}/announcement-preferences', [ScheduleController::class, 'announcementPreferences'])->name('schedules.announcement-preferences');
Route::put('/schedules/{schedule}/announcement-preferences', [ScheduleController::class, 'updateAnnouncementPreferences'])->name('schedules.announcement-preferences.update');
Route::get('/schedules/{schedule}/subscribe', [ScheduleController::class, 'showSubscribe'])->name('schedules.subscribe');
Route::put('/schedules/{schedule}/calendar-settings', [ScheduleController::class, 'updateCalendarSettings'])->name('schedules.calendar-settings.update');
Route::get('/schedules/{schedule}/calendar', ScheduleCalendarController::class)->name('schedules.calendar');

Route::get('/schedules/{schedule}/{term}/learning-progress', [LearningProgressController::class, 'show'])
    ->name('learning-progress.show');
Route::put('/schedules/{schedule}/{term}/learning-progress', [LearningProgressController::class, 'update'])
    ->name('learning-progress.update');

Route::get('/discount-stores', [DiscountStoreController::class, 'index'])->name('discount-stores.index')
    ->withMarkdown(DiscountStoreIndexMarkdownController::class);
Route::get('/discount-stores/create', [DiscountStoreController::class, 'create'])->name('discount-stores.create');
Route::get('/discount-stores/{store}', [DiscountStoreController::class, 'show'])->name('discount-stores.show')
    ->withMarkdown(DiscountStoreShowMarkdownController::class);
Route::post('/discount-stores', [DiscountStoreController::class, 'store'])->name('discount-stores.store');
Route::post('/discount-stores/{store}/reports', [DiscountStoreController::class, 'storeReport'])->name('discount-stores.reports.store');
Route::post('/discount-stores/{store}/comments', [DiscountStoreController::class, 'storeComment'])->name('discount-stores.comments.store');

Route::get('/{type}', [ArticleController::class, 'index'])->name('articles.index')
    ->whereIn('type', ArticleType::cases())
    ->withMarkdown(ArticleIndexMarkdownController::class);
Route::get('/{type}/{slug}', [ArticleController::class, 'show'])->name('articles.show')
    ->whereIn('type', ArticleType::cases())
    ->withMarkdown(ArticleShowMarkdownController::class);
