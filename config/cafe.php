<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Acorns (橡實)
    |--------------------------------------------------------------------------
    |
    | Earned by studying in the study room and by ticking learning progress,
    | each source with its own daily cap (the study room also has a weekly
    | one, Monday to Sunday in Taipei time). Credits expire `expires_after_days`
    | after the day they were earned; spending uses the oldest first.
    |
    */

    'acorns' => [
        'expires_after_days' => 30,

        'study' => [
            'seconds_per_acorn' => 600,
            'daily_cap' => 12,
            'weekly_cap' => 60,
        ],

        'learning_progress' => [
            'week_item' => 1,
            'homework' => 2,
            'daily_cap' => 6,
        ],

        // One-off credit at launch for ticks that predate the acorn system.
        'welcome_cap' => 20,
    ],

];
