<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Room Layout
    |--------------------------------------------------------------------------
    |
    | Each floor has a fixed number of solo seats and shared tables. Shared
    | tables seat several students together; solo seats are one-per-person.
    |
    */

    'layout' => [
        'solo_seats_per_floor' => 12,
        'tables_per_floor' => 3,
        'seats_per_table' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Floors
    |--------------------------------------------------------------------------
    |
    | The next floor opens automatically once every seat on all currently
    | open floors is occupied. `max` caps how many floors can ever open.
    |
    */

    'floors' => [
        'max' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Heartbeat
    |--------------------------------------------------------------------------
    |
    | Occupied seats must be "kept alive" by a periodic heartbeat. A seat
    | that hasn't been seen for `idle_release_seconds` is released back to
    | the pool as if the student had left.
    |
    */

    'heartbeat' => [
        'interval_seconds' => 60,
        'idle_release_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Seat Without Timer
    |--------------------------------------------------------------------------
    |
    | A student who claims a seat but never starts a timer within
    | `grace_seconds` is released back to the pool, since an occupied-but-
    | idle seat blocks other students without anyone actually studying.
    |
    */

    'seat_without_timer' => [
        'grace_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timers
    |--------------------------------------------------------------------------
    |
    | Students run either a pomodoro cycle or a custom timer bounded by
    | `min_minutes`/`max_minutes`. A pomodoro cycle is `rounds_per_cycle`
    | focus rounds of `focus_minutes`, each followed by a
    | `short_break_minutes` break — except the last, which is followed by a
    | `long_break_minutes` break. These are the defaults; each student can
    | tune their own cycle within `bounds`. `max_session_seconds` is a hard
    | backstop so no timer can run away indefinitely.
    |
    */

    'timer' => [
        'pomodoro' => [
            'focus_minutes' => 25,
            'short_break_minutes' => 5,
            'long_break_minutes' => 30,
            'rounds_per_cycle' => 4,
            'bounds' => [
                'focus_minutes' => [5, 120],
                'break_minutes' => [1, 60],
                'rounds_per_cycle' => [1, 12],
            ],
        ],
        'custom' => [
            'min_minutes' => 5,
            'max_minutes' => 180,
        ],
        'max_session_seconds' => 14400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nickname
    |--------------------------------------------------------------------------
    |
    | Nicknames can only be changed once every `cooldown_days` days, to
    | discourage churn/abuse in a shared, identity-light space.
    |
    */

    'nickname' => [
        'cooldown_days' => 7,
        'max_length' => 12,
        'min_length' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Realtime
    |--------------------------------------------------------------------------
    |
    | The room has no polling fallback — it's realtime-only. If the browser
    | hasn't confirmed a Reverb connection within `connect_timeout_seconds`,
    | it gives up and shows a connection-error message instead of retrying
    | forever silently.
    |
    */

    'realtime' => [
        'connect_timeout_seconds' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Open Hours
    |--------------------------------------------------------------------------
    |
    | Display-only label shown in the UI; the room itself is always open.
    |
    */

    'open_hours_label' => '24 小時',

    /*
    |--------------------------------------------------------------------------
    | Location
    |--------------------------------------------------------------------------
    |
    | Where the (virtual) building stands. The floor map's windows and the
    | ground-floor garden follow the real sun and moon at these coordinates
    | and in Asia/Taipei time, so the room looks like daytime or night the
    | way it would on campus — regardless of where the viewer is. Defaults
    | to National Open University's campus in Luzhou, New Taipei.
    |
    */

    'location' => [
        'latitude' => 25.0847,
        'longitude' => 121.4737,
    ],

    /*
    |--------------------------------------------------------------------------
    | Emoji Allowlist
    |--------------------------------------------------------------------------
    |
    | Curated on purpose: only warm, neutral, or encouraging emoji are
    | offered as a student's avatar. Anything that could read as negative,
    | sexual, violent, political, or mocking is deliberately excluded, since
    | this list is shown next to real students studying together.
    |
    */

    'emojis' => [
        '📚', '✏️', '🖊️', '📖', '🧠', '💡',
        '☕', '🍵', '🌱', '🌸', '🍀', '⭐',
        '🌙', '☀️', '🐱', '🐶', '🐰', '🐼',
        '🦉', '🐧', '🍎', '🍞', '🧁', '🎧',
        '🎯', '🔥', '🏃', '🧩',
    ],

];
