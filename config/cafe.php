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

    /*
    |--------------------------------------------------------------------------
    | Coffee
    |--------------------------------------------------------------------------
    |
    | A coffee sits on the buyer's seat (café and study room) for
    | `duration_minutes`; a new order replaces the one still on the table.
    |
    */

    'coffee' => [
        'duration_minutes' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Café Layout
    |--------------------------------------------------------------------------
    |
    | Bar stools along the counter, small two-seat tables by the window and
    | one long communal table. `cafe:sync-seats` reconciles the table with it.
    |
    */

    'layout' => [
        'bar_stools' => 6,
        'window_tables' => 4,
        'seats_per_window_table' => 2,
        'communal_seats' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Heartbeat
    |--------------------------------------------------------------------------
    |
    | A café seat is kept by a heartbeat while the page is open; one not seen
    | for `idle_release_seconds` is given back.
    |
    */

    'heartbeat' => [
        'interval_seconds' => 60,
        'idle_release_seconds' => 1200,
    ],

];
