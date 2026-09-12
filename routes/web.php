<?php

use App\Csp\DocsApiPolicy;
use App\Enums\ArticleType;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseScheduleController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DiscountStoreCommentController;
use App\Http\Controllers\DiscountStoreController;
use App\Http\Controllers\DiscountStoreReportController;
use App\Http\Controllers\DiscountStoreSubmittedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\Markdown\AnnouncementIndexMarkdownController;
use App\Http\Controllers\Markdown\ArticleIndexMarkdownController;
use App\Http\Controllers\Markdown\ArticleShowMarkdownController;
use App\Http\Controllers\Markdown\CourseScheduleMarkdownController;
use App\Http\Controllers\Markdown\CourseShowMarkdownController;
use App\Http\Controllers\Markdown\DirectoryIndexMarkdownController;
use App\Http\Controllers\Markdown\DiscountStoreIndexMarkdownController;
use App\Http\Controllers\Markdown\DiscountStoreShowMarkdownController;
use App\Http\Controllers\Markdown\HomeIndexMarkdownController;
use App\Http\Controllers\Markdown\ScheduleShowMarkdownController;
use App\Http\Controllers\Markdown\StudyRoomMarkdownController;
use App\Http\Controllers\PwaManifestController;
use App\Http\Controllers\ScheduleAnnouncementPreferencesController;
use App\Http\Controllers\ScheduleCalendarController;
use App\Http\Controllers\ScheduleCalendarSettingsUpdateController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleCustomizationController;
use App\Http\Controllers\ScheduleMyController;
use App\Http\Controllers\ScheduleRememberController;
use App\Http\Controllers\ScheduleSubscribeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StudyRoomBreakController;
use App\Http\Controllers\StudyRoomController;
use App\Http\Controllers\StudyRoomHeartbeatController;
use App\Http\Controllers\StudyRoomNextRoundController;
use App\Http\Controllers\StudyRoomProfileController;
use App\Http\Controllers\StudyRoomSeatController;
use App\Http\Controllers\StudyRoomSessionController;
use App\Http\Controllers\StudyRoomStateController;
use App\Http\Controllers\StudyRoomTimerController;
use Illuminate\Support\Facades\Route;
use Spatie\Csp\AddCspHeaders;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::view('/docs/api', 'redocly')
    ->middleware(AddCspHeaders::class.':'.DocsApiPolicy::class)
    ->name('docs.api.view');
Route::get('/docs/api.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/yaml; charset=utf-8',
    ]);
})->name('docs.api.yaml');

Route::get('/manifest.webmanifest', PwaManifestController::class)->name('pwa.manifest');

Route::get('/', [HomeController::class, 'index'])->name('home')
    ->withMarkdown(HomeIndexMarkdownController::class, uri: '/llms.txt', name: 'llms-txt');
Route::redirect('/ai.txt', '/llms.txt', 301);

Route::view('/alt-uu', 'alt-uu')->name('alt-uu');

Route::view('/offline', 'offline')->name('offline');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index')
    ->withMarkdown(AnnouncementIndexMarkdownController::class);

Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index')
    ->withMarkdown(DirectoryIndexMarkdownController::class);

Route::get('/courses/schedule', CourseScheduleController::class)->name('course.schedule')
    ->withMarkdown(CourseScheduleMarkdownController::class);

Route::get('/courses/{course}', [CourseController::class, 'show'])->name('course.show')
    ->withMarkdown(CourseShowMarkdownController::class);

Route::permanentRedirect('/schedule/create', '/schedules/create');
Route::permanentRedirect('/schedule/{schedule}', '/schedules/{schedule}');
Route::permanentRedirect('/schedule/{schedule}/edit', '/schedules/{schedule}/edit');
Route::permanentRedirect('/schedule/{schedule}/calendar', '/schedules/{schedule}/calendar');

Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
Route::get('/schedules/my', ScheduleMyController::class)->name('schedules.my');
Route::get('/schedules/{schedule}', [ScheduleController::class, 'show'])->name('schedules.show')
    ->withMarkdown(ScheduleShowMarkdownController::class);
Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
Route::post('/schedules/{schedule}/remember', ScheduleRememberController::class)->name('schedules.remember');
Route::get('/schedules/{schedule}/customize', [ScheduleCustomizationController::class, 'edit'])->name('schedules.customize');
Route::put('/schedules/{schedule}/customize', [ScheduleCustomizationController::class, 'update'])->name('schedules.customize.update');
Route::get('/schedules/{schedule}/announcement-preferences', [ScheduleAnnouncementPreferencesController::class, 'edit'])->name('schedules.announcement-preferences');
Route::put('/schedules/{schedule}/announcement-preferences', [ScheduleAnnouncementPreferencesController::class, 'update'])->name('schedules.announcement-preferences.update');
Route::get('/schedules/{schedule}/subscribe', ScheduleSubscribeController::class)->name('schedules.subscribe');
Route::put('/schedules/{schedule}/calendar-settings', ScheduleCalendarSettingsUpdateController::class)->name('schedules.calendar-settings.update');
Route::get('/schedules/{schedule}/calendar', ScheduleCalendarController::class)->name('schedules.calendar');

Route::get('/schedules/{schedule}/{term}/learning-progress', [LearningProgressController::class, 'show'])
    ->name('learning-progress.show');
Route::put('/schedules/{schedule}/{term}/learning-progress', [LearningProgressController::class, 'update'])
    ->name('learning-progress.update');

Route::get('/discount-stores', [DiscountStoreController::class, 'index'])->name('discount-stores.index')
    ->withMarkdown(DiscountStoreIndexMarkdownController::class);
Route::get('/discount-stores/create', [DiscountStoreController::class, 'create'])->name('discount-stores.create');
Route::get('/discount-stores/submitted', DiscountStoreSubmittedController::class)->name('discount-stores.submitted');
Route::get('/discount-stores/{store}', [DiscountStoreController::class, 'show'])->name('discount-stores.show')
    ->withMarkdown(DiscountStoreShowMarkdownController::class);
Route::post('/discount-stores', [DiscountStoreController::class, 'store'])->name('discount-stores.store');
Route::post('/discount-stores/{store}/reports', [DiscountStoreReportController::class, 'store'])->name('discount-stores.reports.store');
Route::post('/discount-stores/{store}/comments', [DiscountStoreCommentController::class, 'store'])->name('discount-stores.comments.store');

// Registered outside the study-room group: withMarkdown() registers its
// sibling route from inside the current group, so a prefixed group would
// apply the prefix twice (/study-room/study-room.md).
Route::get('/study-room', [StudyRoomController::class, 'show'])->name('study-room.show')
    ->withMarkdown(StudyRoomMarkdownController::class);

Route::prefix('study-room')->name('study-room.')->group(function (): void {
    Route::get('/state', StudyRoomStateController::class)->name('state')->middleware('throttle:120,1');
    Route::get('/sessions', StudyRoomSessionController::class)->name('sessions')->middleware('throttle:60,1');
    Route::post('/profile', StudyRoomProfileController::class)->name('profile.update')->middleware('throttle:5,1');
    Route::post('/seats/{seat}/take', [StudyRoomSeatController::class, 'store'])->name('seats.take')->middleware('throttle:60,1');
    Route::post('/seat/leave', [StudyRoomSeatController::class, 'destroy'])->name('seat.leave')->middleware('throttle:60,1');
    Route::post('/timer', [StudyRoomTimerController::class, 'store'])->name('timer.start')->middleware('throttle:60,1');
    Route::delete('/timer', [StudyRoomTimerController::class, 'destroy'])->name('timer.stop')->middleware('throttle:60,1');
    Route::post('/timer/break', StudyRoomBreakController::class)->name('timer.break')->middleware('throttle:60,1');
    Route::post('/timer/next', StudyRoomNextRoundController::class)->name('timer.next')->middleware('throttle:60,1');
    Route::post('/heartbeat', StudyRoomHeartbeatController::class)->name('heartbeat')->middleware('throttle:60,1');
});

Route::get('/{type}', [ArticleController::class, 'index'])->name('articles.index')
    ->whereIn('type', ArticleType::cases())
    ->withMarkdown(ArticleIndexMarkdownController::class);
Route::get('/{type}/{slug}', [ArticleController::class, 'show'])->name('articles.show')
    ->whereIn('type', ArticleType::cases())
    ->withMarkdown(ArticleShowMarkdownController::class);
