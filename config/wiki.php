<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 空大知識庫 (wiki)
    |--------------------------------------------------------------------------
    |
    | Nobody edits wiki text directly: a visitor describes the change, the AI
    | editor rewrites the page and the draft is submitted at once. Low-risk
    | edits publish on their own (see `auto_publish`), everything else waits
    | for an admin in Filament.
    |
    */

    'title' => '空大知識庫',

    // Hosts (subdomains included) a submitter may cite and the editor may
    // fetch. Anything else is rejected at submission and never fetched.
    'source_domains' => ['nou.edu.tw', 'moe.gov.tw', 'edu.tw', 'gov.tw'],

    // `pdftotext` (poppler) reads PDF sources for providers without a
    // server-side fetch. Web workers often lack Homebrew on PATH, so set the
    // full path here if it is not found automatically.
    'pdftotext_path' => env('WIKI_PDFTOTEXT_PATH'),

    // Cited URLs and uploaded screenshots share this cap per edit.
    'max_references' => 10,

    'screenshot_max_kb' => 5120,

    'instruction_max_length' => 2000,

    'max_refinements' => 3,

    'max_body_chars' => 30000,

    'max_title_length' => 80,

    'max_categories' => 5,

    // Comma-separated student_schedules.id values (WIKI_UNLIMITED_SCHEDULE_IDS)
    // for trusted editors: no draft or request rate limits, and their edits
    // publish without review.
    'unlimited_schedule_ids' => array_values(array_filter(
        array_map('intval', explode(',', (string) env('WIKI_UNLIMITED_SCHEDULE_IDS', ''))),
    )),

    'rate_limits' => [
        // Every draft the editor writes counts: the first one, each 再調整
        // and each redraft after a conflict.
        'per_schedule_per_hour' => 5,
        'per_schedule_per_day' => 15,
        'per_ip_per_hour' => 10,
    ],

    'auto_publish' => [
        'enabled' => (bool) env('WIKI_AUTO_PUBLISH', true),

        // Share of the base page's lines that may change (added + removed,
        // over the larger of the two versions).
        'max_changed_ratio' => 0.3,

        'max_removed_chars' => 800,

        // Another device's edit within this window sends the next one to
        // review, which damps edit wars.
        'cooldown_minutes' => 30,
    ],

    'ai' => [
        'provider' => env('WIKI_AI_PROVIDER', 'anthropic'),
        'model' => env('WIKI_AI_MODEL', 'claude-sonnet-5-5'),

        // Each fetch costs roughly 10–20k input tokens.
        'max_fetches' => (int) env('WIKI_AI_MAX_FETCHES', 4),

        // Drafts per Taipei day across the whole site, a cost backstop on
        // top of the per-device limits.
        'daily_cap' => (int) env('WIKI_AI_DAILY_CAP', 200),
    ],

];
