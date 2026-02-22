@php
    $baseEmail = 'nou-tools-error@binota.org';
    $subject = 'NOU TOOLS 網頁錯誤 - ' . Date::now()->toAtomString();
    $info = [
        'UA' => request()->header('User-Agent'),
        'Time' => now(),
        'Path' => request()->path(),
        'Method' => request()->method(),
    ];

    $mailtoLink = sprintf(
        'mailto:%s?subject=%s&body=%s',
        $baseEmail,
        rawurlencode($subject),
        rawurlencode(
            "\n\n請將您的訊息寫在此行上方\n--------\n" .
                collect($info)
                    ->map(fn ($value, $key) => "{$key}: {$value}")
                    ->join("\n"),
        ),
    );
@endphp

<!DOCTYPE html>
<html lang="zh-hant">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="robots" content="noindex, nofollow" />

        <title>@yield('title')</title>

        <style>
                    /*! tailwindcss v4.2.0 | MIT License | https://tailwindcss.com */
            @layer properties;
            @layer theme, base, components, utilities;
            @layer theme {
              :root, :host {
                --font-sans: system-ui, sans-serif;
                --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New',
                monospace;
                --color-white: #fff;
                --spacing: 0.25rem;
                --container-md: 28rem;
                --container-7xl: 80rem;
                --text-lg: 1.125rem;
                --text-lg--line-height: calc(1.75 / 1.125);
                --text-2xl: 1.5rem;
                --text-2xl--line-height: calc(2 / 1.5);
                --text-4xl: 2.25rem;
                --text-4xl--line-height: calc(2.5 / 2.25);
                --font-weight-medium: 500;
                --font-weight-semibold: 600;
                --font-weight-bold: 700;
                --radius-md: 0.375rem;
                --radius-lg: 0.5rem;
                --default-transition-duration: 150ms;
                --default-transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
                --default-font-family: var(--font-sans);
                --default-mono-font-family: var(--font-mono);
                --color-warm-50: oklch(0.98 0.01 40);
                --color-warm-200: oklch(0.93 0.04 40);
                --color-warm-600: oklch(0.65 0.15 35);
                --color-warm-700: oklch(0.55 0.13 35);
                --color-warm-900: oklch(0.35 0.08 35);
              }
            }
            @layer base {
              *, ::after, ::before, ::backdrop, ::file-selector-button {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                border: 0 solid;
              }
              html, :host {
                line-height: 1.5;
                -webkit-text-size-adjust: 100%;
                tab-size: 4;
                font-family: var(--default-font-family, ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji');
                font-feature-settings: var(--default-font-feature-settings, normal);
                font-variation-settings: var(--default-font-variation-settings, normal);
                -webkit-tap-highlight-color: transparent;
              }
              hr {
                height: 0;
                color: inherit;
                border-top-width: 1px;
              }
              abbr:where([title]) {
                -webkit-text-decoration: underline dotted;
                text-decoration: underline dotted;
              }
              h1, h2, h3, h4, h5, h6 {
                font-size: inherit;
                font-weight: inherit;
              }
              a {
                color: inherit;
                -webkit-text-decoration: inherit;
                text-decoration: inherit;
              }
              b, strong {
                font-weight: bolder;
              }
              code, kbd, samp, pre {
                font-family: var(--default-mono-font-family, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace);
                font-feature-settings: var(--default-mono-font-feature-settings, normal);
                font-variation-settings: var(--default-mono-font-variation-settings, normal);
                font-size: 1em;
              }
              small {
                font-size: 80%;
              }
              sub, sup {
                font-size: 75%;
                line-height: 0;
                position: relative;
                vertical-align: baseline;
              }
              sub {
                bottom: -0.25em;
              }
              sup {
                top: -0.5em;
              }
              table {
                text-indent: 0;
                border-color: inherit;
                border-collapse: collapse;
              }
              :-moz-focusring {
                outline: auto;
              }
              progress {
                vertical-align: baseline;
              }
              summary {
                display: list-item;
              }
              ol, ul, menu {
                list-style: none;
              }
              img, svg, video, canvas, audio, iframe, embed, object {
                display: block;
                vertical-align: middle;
              }
              img, video {
                max-width: 100%;
                height: auto;
              }
              button, input, select, optgroup, textarea, ::file-selector-button {
                font: inherit;
                font-feature-settings: inherit;
                font-variation-settings: inherit;
                letter-spacing: inherit;
                color: inherit;
                border-radius: 0;
                background-color: transparent;
                opacity: 1;
              }
              :where(select:is([multiple], [size])) optgroup {
                font-weight: bolder;
              }
              :where(select:is([multiple], [size])) optgroup option {
                padding-inline-start: 20px;
              }
              ::file-selector-button {
                margin-inline-end: 4px;
              }
              ::placeholder {
                opacity: 1;
              }
              @supports (not (-webkit-appearance: -apple-pay-button)) or (contain-intrinsic-size: 1px) {
                ::placeholder {
                  color: currentcolor;
                  @supports (color: color-mix(in lab, red, red)) {
                    color: color-mix(in oklab, currentcolor 50%, transparent);
                  }
                }
              }
              textarea {
                resize: vertical;
              }
              ::-webkit-search-decoration {
                -webkit-appearance: none;
              }
              ::-webkit-date-and-time-value {
                min-height: 1lh;
                text-align: inherit;
              }
              ::-webkit-datetime-edit {
                display: inline-flex;
              }
              ::-webkit-datetime-edit-fields-wrapper {
                padding: 0;
              }
              ::-webkit-datetime-edit, ::-webkit-datetime-edit-year-field, ::-webkit-datetime-edit-month-field, ::-webkit-datetime-edit-day-field, ::-webkit-datetime-edit-hour-field, ::-webkit-datetime-edit-minute-field, ::-webkit-datetime-edit-second-field, ::-webkit-datetime-edit-millisecond-field, ::-webkit-datetime-edit-meridiem-field {
                padding-block: 0;
              }
              ::-webkit-calendar-picker-indicator {
                line-height: 1;
              }
              :-moz-ui-invalid {
                box-shadow: none;
              }
              button, input:where([type='button'], [type='reset'], [type='submit']), ::file-selector-button {
                appearance: button;
              }
              ::-webkit-inner-spin-button, ::-webkit-outer-spin-button {
                height: auto;
              }
              [hidden]:where(:not([hidden='until-found'])) {
                display: none!important;
              }
            }
            @layer utilities {
              .sticky {
                position: sticky;
              }
              .top-0 {
                top: calc(var(--spacing) * 0);
              }
              .z-40 {
                z-index: 40;
              }
              .mx-auto {
                margin-inline: auto;
              }
              .mb-2 {
                margin-bottom: calc(var(--spacing) * 2);
              }
              .mb-4 {
                margin-bottom: calc(var(--spacing) * 4);
              }
              .mb-6 {
                margin-bottom: calc(var(--spacing) * 6);
              }
              .flex {
                display: flex;
              }
              .inline-flex {
                display: inline-flex;
              }
              .size-5 {
                width: calc(var(--spacing) * 5);
                height: calc(var(--spacing) * 5);
              }
              .min-h-\[60vh\] {
                min-height: 60vh;
              }
              .w-full {
                width: 100%;
              }
              .max-w-7xl {
                max-width: var(--container-7xl);
              }
              .max-w-md {
                max-width: var(--container-md);
              }
              .flex-1 {
                flex: 1;
              }
              .shrink-0 {
                flex-shrink: 0;
              }
              .items-center {
                align-items: center;
              }
              .justify-center {
                justify-content: center;
              }
              .gap-2 {
                gap: calc(var(--spacing) * 2);
              }
              .gap-3 {
                gap: calc(var(--spacing) * 3);
              }
              .rounded-lg {
                border-radius: var(--radius-lg);
              }
              .rounded-md {
                border-radius: var(--radius-md);
              }
              .border {
                border-style: var(--tw-border-style);
                border-width: 1px;
              }
              .border-b {
                border-bottom-style: var(--tw-border-style);
                border-bottom-width: 1px;
              }
              .border-warm-200 {
                border-color: var(--color-warm-200);
              }
              .bg-warm-50 {
                background-color: var(--color-warm-50);
              }
              .bg-warm-600 {
                background-color: var(--color-warm-600);
              }
              .bg-white {
                background-color: var(--color-white);
              }
              .p-8 {
                padding: calc(var(--spacing) * 8);
              }
              .px-3 {
                padding-inline: calc(var(--spacing) * 3);
              }
              .px-4 {
                padding-inline: calc(var(--spacing) * 4);
              }
              .px-6 {
                padding-inline: calc(var(--spacing) * 6);
              }
              .py-2 {
                padding-block: calc(var(--spacing) * 2);
              }
              .py-8 {
                padding-block: calc(var(--spacing) * 8);
              }
              .text-center {
                text-align: center;
              }
              .text-2xl {
                font-size: var(--text-2xl);
                line-height: var(--tw-leading, var(--text-2xl--line-height));
              }
              .text-4xl {
                font-size: var(--text-4xl);
                line-height: var(--tw-leading, var(--text-4xl--line-height));
              }
              .text-lg {
                font-size: var(--text-lg);
                line-height: var(--tw-leading, var(--text-lg--line-height));
              }
              .font-bold {
                --tw-font-weight: var(--font-weight-bold);
                font-weight: var(--font-weight-bold);
              }
              .font-medium {
                --tw-font-weight: var(--font-weight-medium);
                font-weight: var(--font-weight-medium);
              }
              .font-semibold {
                --tw-font-weight: var(--font-weight-semibold);
                font-weight: var(--font-weight-semibold);
              }
              .text-warm-600 {
                color: var(--color-warm-600);
              }
              .text-warm-700 {
                color: var(--color-warm-700);
              }
              .text-warm-900 {
                color: var(--color-warm-900);
              }
              .text-white {
                color: var(--color-white);
              }
              .underline {
                text-decoration-line: underline;
              }
              .transition-colors {
                transition-property: color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, --tw-gradient-from, --tw-gradient-via, --tw-gradient-to;
                transition-timing-function: var(--tw-ease, var(--default-transition-timing-function));
                transition-duration: var(--tw-duration, var(--default-transition-duration));
              }
              .hover\:bg-warm-50 {
                &:hover {
                  @media (hover: hover) {
                    background-color: var(--color-warm-50);
                  }
                }
              }
              .hover\:bg-warm-700 {
                &:hover {
                  @media (hover: hover) {
                    background-color: var(--color-warm-700);
                  }
                }
              }
              .hover\:no-underline {
                &:hover {
                  @media (hover: hover) {
                    text-decoration-line: none;
                  }
                }
              }
              .md\:size-6 {
                @media (width >= 48rem) {
                  width: calc(var(--spacing) * 6);
                  height: calc(var(--spacing) * 6);
                }
              }
              .md\:gap-4 {
                @media (width >= 48rem) {
                  gap: calc(var(--spacing) * 4);
                }
              }
              .md\:px-6 {
                @media (width >= 48rem) {
                  padding-inline: calc(var(--spacing) * 6);
                }
              }
              .md\:py-4 {
                @media (width >= 48rem) {
                  padding-block: calc(var(--spacing) * 4);
                }
              }
              .md\:text-2xl {
                @media (width >= 48rem) {
                  font-size: var(--text-2xl);
                  line-height: var(--tw-leading, var(--text-2xl--line-height));
                }
              }
            }
            @layer base {
              [x-cloak], [data-cloak], .x-cloak {
                display: none!important;
              }
            }
            @property --tw-border-style {
              syntax: "*";
              inherits: false;
              initial-value: solid;
            }
            @property --tw-font-weight {
              syntax: "*";
              inherits: false;
            }
            @layer properties {
              @supports ((-webkit-hyphens: none) and (not (margin-trim: inline))) or ((-moz-orient: inline) and (not (color:rgb(from red r g b)))) {
                *, ::before, ::after, ::backdrop {
                  --tw-border-style: solid;
                  --tw-font-weight: initial;
                }
              }
            }
        </style>
    </head>
    <body class="bg-warm-50 text-warm-900">
        <header class="sticky top-0 z-40 border-b border-warm-200 bg-white">
            <div class="mx-auto max-w-7xl px-3 py-2 md:px-6 md:py-4">
                <h1
                    class="inline-flex items-center gap-2 text-lg font-bold text-warm-700 md:gap-4 md:text-2xl"
                >
                    <svg
                        class="size-5 shrink-0 text-warm-700 md:size-6"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path
                            d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"
                        ></path>
                    </svg>
                    <a href="{{ url('/') }}" class="shrink-0">NOU 小幫手</a>
                </h1>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-8">
            <div class="flex min-h-[60vh] items-center justify-center">
                <div class="w-full max-w-md">
                    <div class="rounded-lg border border-warm-200 bg-white p-8">
                        <div class="mb-6 text-center">
                            <h2 class="mb-2 text-4xl font-bold text-warm-600">
                                @yield('code')
                            </h2>
                            <p
                                class="mb-4 text-2xl font-semibold text-warm-900"
                            >
                                @yield('message')
                            </p>
                            <p class="text-warm-900">
                                抱歉，發生了一些問題。如果問題持續，請
                                <a
                                    class="text-warm-600 underline hover:no-underline"
                                    href="{{ $mailtoLink }}"
                                >
                                    點擊此連結寫信聯絡網站作者
                                </a>
                                。
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <button
                                onclick="history.back()"
                                class="flex-1 rounded-md border border-warm-200 px-4 py-2 text-center font-medium text-warm-700 transition-colors hover:bg-warm-50"
                            >
                                回到上一頁
                            </button>
                            <a
                                href="{{ url('/') }}"
                                class="flex-1 rounded-md bg-warm-600 px-4 py-2 text-center font-medium text-white transition-colors hover:bg-warm-700"
                            >
                                回到首頁
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
