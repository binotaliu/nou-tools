<?php

use App\Console\Commands\DraftNewsletterCommand;
use App\Console\Commands\FetchAnnouncementsCommand;
use App\Console\Commands\PublishDueNewsletterIssuesCommand;
use App\Console\Commands\ReleaseIdleStudyRoomSeatsCommand;
use App\Console\Commands\SendClassStartingRemindersCommand;
use Illuminate\Support\Facades\Schedule;

// NOTE: schedule_timezone is set to Asia/Taipei in config/app.php.

Schedule::command(ReleaseIdleStudyRoomSeatsCommand::class)->everyMinute();

Schedule::command(SendClassStartingRemindersCommand::class)->everyMinute();

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
