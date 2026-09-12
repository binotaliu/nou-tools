<?php

use App\Console\Commands\FetchAnnouncementsCommand;
use App\Console\Commands\ReleaseIdleStudyRoomSeatsCommand;
use App\Console\Commands\ReleaseStudyRoomSeatsWithoutTimerCommand;
use Illuminate\Support\Facades\Schedule;

// NOTE: schedule_timezone is set to Asia/Taipei in config/app.php.

Schedule::command(ReleaseIdleStudyRoomSeatsCommand::class)->everyMinute();
Schedule::command(ReleaseStudyRoomSeatsWithoutTimerCommand::class)->everyMinute();

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
