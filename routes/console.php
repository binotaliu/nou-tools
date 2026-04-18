<?php

use App\Console\Commands\FetchAnnouncementsCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(FetchAnnouncementsCommand::class)
    ->everySixHours();
