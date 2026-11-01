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
    | The drink arrives `delivery_seconds` after it is ordered and takes
    | `sips` sips to finish. 內用 is thrown away when the buyer leaves the
    | café seat; 外帶 keeps its lifetime.
    |
    */

    'coffee' => [
        'duration_minutes' => 120,
        'delivery_seconds' => 6,
        'sips' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sticky notes
    |--------------------------------------------------------------------------
    |
    | Bought with acorns after a coffee that day, at most one a day. A note is
    | screened by an AI model before it goes on the wall; the model can only
    | let a note through or hold it for an admin, never turn it down.
    |
    */

    'notes' => [
        'price' => 5,
        'lifetime_days' => 14,
        'max_text_length' => 60,
        'drawing_size' => 480,
        'drawing_max_kb' => 300,
        'paper_colors' => ['yellow', 'pink', 'blue', 'green', 'orange'],
        'wall_limit' => 60,
        // Readers' reports hide a note for review at this many.
        'report_threshold' => 3,

        'ai' => [
            'provider' => env('CAFE_AI_PROVIDER', 'anthropic'),
            'model' => env('CAFE_AI_MODEL', 'claude-haiku-5-5'),
        ],
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
    | Seat
    |--------------------------------------------------------------------------
    |
    | A café seat is for having something: a focus timer cannot start without
    | a drink on the table, and a student who has ordered nothing
    | `order_grace_seconds` after sitting down is asked to leave the seat.
    |
    */

    'seat' => [
        'order_grace_seconds' => 180,
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
