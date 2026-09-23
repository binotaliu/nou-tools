<?php

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ChangelogPost;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\DiscountStore;
use App\Models\DiscountStoreComment;
use App\Models\DiscountStoreReport;
use App\Models\MusicPlaylist;
use App\Models\MusicTrack;
use App\Models\NewsletterColumn;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use App\Models\NewsletterReaction;
use App\Models\PushNotificationDelivery;
use App\Models\StudentScheduleItem;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use NouTools\Domains\Schedules\Actions\GenerateScheduleCalendar;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Resource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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

        // Override Eloquent's protected `casts()` / `booted()` hooks, per Laravel convention.
        Announcement::class,
        ChangelogPost::class,
        ClassSchedule::class,
        Course::class,
        CourseClass::class,
        DiscountStore::class,
        DiscountStoreComment::class,
        DiscountStoreReport::class,
        MusicPlaylist::class,
        MusicTrack::class,
        NewsletterColumn::class,
        NewsletterIssue::class,
        NewsletterItem::class,
        NewsletterReaction::class,
        PushNotificationDelivery::class,
        StudentScheduleItem::class,
        User::class,

        // Filament resource/page/relation-manager classes are built around
        // Filament's own protected hook overrides (getHeaderActions, form,
        // table, infolist, ...); not a code smell.
        'App\Filament',
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
    ->classes()
    ->toExtend(Data::class)
    ->classes()
    ->toHaveConstructor()
    ->classes()
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

test('No named functions in test files: assign a closure to a variable and pass it with `use` instead', function () {
    $violations = collect(Finder::create()->files()->in(dirname(__DIR__))->name('*.php')
        // Pest.php is loaded once, so its shared helpers cannot collide.
        ->notName('Pest.php'))
        ->flatMap(fn (SplFileInfo $file) => collect(preg_split('/\R/', $file->getContents()))
            ->filter(fn (string $line) => preg_match('/^\s*function\s+&?\w+\s*\(/', $line) === 1)
            ->map(fn (string $line) => $file->getRelativePathname().': '.trim($line)))
        ->values()
        ->all();

    expect($violations)->toBeEmpty();
});
