<?php

use App\Console\Commands\FetchAnnouncementsCommand;
use Illuminate\Support\Facades\Schedule;

// NOTE: schedule_timezone is set to Asia/Taipei in config/app.php.

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
