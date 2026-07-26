<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CommonMark environment options
    |--------------------------------------------------------------------------
    |
    | Passed straight into the League\CommonMark Environment. Articles are
    | trusted repository files today, but we still strip raw HTML (rather
    | than rendering it as visible escaped text, or trusting it outright)
    | and disallow unsafe links (javascript:, data:, ...) defensively - this
    | also keeps authoring comments like `<!-- placeholder: ... -->` invisible
    | and protects us if article content ever becomes user-authored (e.g. via
    | a future Filament editor).
    */
    'commonmark' => [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Callout types
    |--------------------------------------------------------------------------
    |
    | Registered for both the GitHub alert syntax (`> [!TIP]`) and the
    | `:::tip` container alias form. Keys are lowercase slugs; `label` is the
    | default Chinese title shown when the author doesn't supply a custom
    | title, and `icon` is a Blade Icons name (heroicons) rendered as an inline
    | SVG in `.md-callout-icon`.
    */
    'callouts' => [
        'note' => ['label' => '補充說明', 'icon' => 'heroicon-o-information-circle'],
        'tip' => ['label' => '小撇步', 'icon' => 'heroicon-o-light-bulb'],
        'important' => ['label' => '重要', 'icon' => 'heroicon-o-exclamation-circle'],
        'warning' => ['label' => '注意', 'icon' => 'heroicon-o-exclamation-triangle'],
        'caution' => ['label' => '當心', 'icon' => 'heroicon-o-shield-exclamation'],
        'money' => ['label' => '費用', 'icon' => 'heroicon-o-banknotes'],
        'time' => ['label' => '時程', 'icon' => 'heroicon-o-calendar-days'],
        'exam' => ['label' => '考試', 'icon' => 'heroicon-o-pencil-square'],
        'experience' => ['label' => '學長姐經驗談', 'icon' => 'heroicon-o-academic-cap'],
        'mistake' => ['label' => '常見錯誤', 'icon' => 'heroicon-o-no-symbol'],
    ],
];
