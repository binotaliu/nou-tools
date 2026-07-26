<?php

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\DiscountStore;
use App\Models\DiscountStoreComment;
use App\Models\DiscountStoreReport;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\View\Components\Button;
use App\View\Components\LinkButton;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use NouTools\Domains\Schedules\Actions\GenerateScheduleCalendar;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Resource;

arch()->preset()->php();
arch()->preset()->security()
    ->ignoring([
        // Hashes an iCal event UID, not sensitive data.
        GenerateScheduleCalendar::class,
    ]);
arch()->preset()->laravel()
    ->ignoring([
        // Filament panel providers follow Filament's own naming convention.
        'App\Providers\Filament',
    ]);
arch()->preset()->strict()
    ->ignoring([
        // Base controller class, meant to be extended.
        Controller::class,

        // Override Eloquent's protected `casts()` hook, per Laravel convention.
        Announcement::class,
        ClassSchedule::class,
        Course::class,
        CourseClass::class,
        DiscountStore::class,
        DiscountStoreComment::class,
        DiscountStoreReport::class,
        User::class,

        // Filament resource/page/relation-manager classes are built around
        // Filament's own protected hook overrides (getHeaderActions, form,
        // table, infolist, ...); not a code smell.
        'App\Filament',

        // LinkButton extends Button and overrides its protected style helpers.
        Button::class,
        LinkButton::class,
    ]);

arch('Actions')
    ->expect('NouTools\Domains\*\Actions')
    ->not->toHavePublicMethodsBesides(['__invoke', '__construct']);

arch('ViewModels')
    ->expect('NouTools\Domains\*\ViewModels')
    ->toExtend(Data::class)
    ->toHaveConstructor()
    ->toHaveOnlyCamelCasePublicProperties();

arch('PageData')
    ->expect('NouTools\Domains\*\PageData')
    ->toExtend(Resource::class)
    ->toHaveConstructor()
    ->toHaveOnlyCamelCasePublicProperties();

arch('DTOs')
    ->expect('NouTools\Domains\*\DataTransferObjects')
    ->toExtend(Data::class)
    ->toHaveConstructor()
    ->toHaveOnlyCamelCasePublicProperties();

arch('No Directly File Read/Write: use the File facade or Storage facade instead')
    ->expect(['file_get_contents', 'file_put_contents'])
    ->not->toBeUsed();

arch('No use of Carbon/CarbonImmutable directly: use the Date facade instead')
    ->expect([Carbon::class, CarbonImmutable::class, Illuminate\Support\Carbon::class])
    ->not->toBeUsed()
    ->ignoring([
        // Have `Date::use(CarbonImmutable::class)`
        AppServiceProvider::class,
    ]);
