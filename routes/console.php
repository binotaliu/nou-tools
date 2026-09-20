<?php

use App\Console\Commands\DraftNewsletterCommand;
use App\Console\Commands\FetchAnnouncementsCommand;
use App\Console\Commands\PublishDueNewsletterIssuesCommand;
use App\Console\Commands\ReleaseIdleStudyRoomSeatsCommand;
use App\Console\Commands\SendClassStartingRemindersCommand;
use Illuminate\Support\Facades\Schedule;
use NouTools\Domains\StudyRoom\Actions\SendStudyTimerEndPushes;

// NOTE: schedule_timezone is set to Asia/Taipei in config/app.php.

Schedule::command(ReleaseIdleStudyRoomSeatsCommand::class)->everyMinute();

Schedule::command(SendClassStartingRemindersCommand::class)->everyMinute();

// 自習室計時器結束推播。Sub-minute so the notification lands within ~10s of
// zero instead of up to a minute late, which a countdown would feel.
// Registered as a callback rather than a command because `Schedule::command`
// shells out to `php artisan`, and at this frequency that would be six extra
// Laravel boots a minute; `study-room:send-timer-end-pushes` still exists for
// running the same sweep by hand.
Schedule::call(fn () => app(SendStudyTimerEndPushes::class)())->everyTenSeconds();

Schedule::command(FetchAnnouncementsCommand::class)
    ->weekdays()
    ->everyThirtyMinutes()
    ->between('08:00', '22:00');

Schedule::command(FetchAnnouncementsCommand::class)
    ->weekdays()
    ->everyTwoHours()
    ->between('00:00', '08:00');

Schedule::command(FetchAnnouncementsCommand::class)
    ->weekends()
    ->everyTwoHours();

// 浣熊的空大雙週報: ready issues go out Monday morning; the next issue's draft
// (with its AI first pass) is created as its editing week begins.
Schedule::command(PublishDueNewsletterIssuesCommand::class)
    ->mondays()
    ->at('08:00');

Schedule::command(DraftNewsletterCommand::class)
    ->mondays()
    ->at('09:00');
