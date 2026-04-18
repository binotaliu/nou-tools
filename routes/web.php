<?php

use App\Enums\ArticleType;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\ScheduleCalendarController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::view('/docs/api', 'redocly')->name('docs.api.view');
Route::get('/docs/api.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/yaml; charset=utf-8',
    ]);
})->name('docs.api.yaml');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::view('/alt-uu', 'alt-uu')->name('alt-uu');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

Route::get('/courses/{course}', [CourseController::class, 'show'])->name('course.show');

Route::permanentRedirect('/schedule/create', '/schedules/create');
Route::permanentRedirect('/schedule/{schedule}', '/schedules/{schedule}');
Route::permanentRedirect('/schedule/{schedule}/edit', '/schedules/{schedule}/edit');
Route::permanentRedirect('/schedule/{schedule}/calendar', '/schedules/{schedule}/calendar');

Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
Route::get('/schedules/{schedule}', [ScheduleController::class, 'show'])->name('schedules.show');
Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
Route::get('/schedules/{schedule}/customize', [ScheduleController::class, 'customize'])->name('schedules.customize');
Route::put('/schedules/{schedule}/customize', [ScheduleController::class, 'updateCustomization'])->name('schedules.customize.update');
Route::get('/schedules/{schedule}/calendar', ScheduleCalendarController::class)->name('schedules.calendar');

Route::get('/schedules/{schedule}/{term}/learning-progress', [LearningProgressController::class, 'show'])
    ->name('learning-progress.show');
Route::put('/schedules/{schedule}/{term}/learning-progress', [LearningProgressController::class, 'update'])
    ->name('learning-progress.update');

Route::get('/{type}', [ArticleController::class, 'index'])->name('articles.index')
    ->whereIn('type', ArticleType::cases());
Route::get('/{type}/{slug}', [ArticleController::class, 'show'])->name('articles.show')
    ->whereIn('type', ArticleType::cases());
