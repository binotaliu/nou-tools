<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 浣熊的空大雙週報
    |--------------------------------------------------------------------------
    |
    | Issues are published every other Monday, counted in 14-day steps from
    | this anchor Monday (2026-W39). ISO week parity can't be used instead:
    | 53-week years such as 2026 flip it (2026-W53 is followed by 2027-W02).
    |
    */

    'title' => '浣熊的空大雙週報',

    'anchor_date' => env('NEWSLETTER_ANCHOR_DATE', '2026-09-21'),

    'cadence_days' => 14,

    'ai' => [
        'provider' => env('NEWSLETTER_AI_PROVIDER', 'anthropic'),
        'model' => env('NEWSLETTER_AI_MODEL', 'claude-sonnet-5'),
        'max_items_per_section' => 12,
        'max_candidates_per_section' => 300,
    ],

];
