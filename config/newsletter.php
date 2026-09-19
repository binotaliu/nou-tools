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

        // The curator may open an announcement's page (HTML or PDF) when its
        // title alone isn't enough to summarise it. Each fetch costs roughly
        // 10–20k input tokens, so it's capped per section, and limited to
        // school hosts (subdomains included) so short links and third-party
        // sites are never fetched.
        'max_fetches_per_section' => (int) env('NEWSLETTER_AI_MAX_FETCHES', 12),
        'fetch_domains' => ['nou.edu.tw'],
    ],

];
