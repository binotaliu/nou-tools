@props([
    'callback' => '',
    'errorCallback' => '',
    'theme' => 'auto',
    'language' => 'en-US',
    'size' => 'normal',
    'explicit' => false,
])

<div
    {{
        $attributes->merge([
            'class' => $explicit ? '' : 'cf-turnstile',
            'data-sitekey' => config('turnstile.turnstile_site_key'),
            'data-callback' => $callback,
            'data-error-callback' => $errorCallback,
            'data-theme' => $theme,
            'data-language' => $language,
            'data-size' => $size,
        ])
    }}
></div>

@pushOnce('head')
    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        defer
    ></script>
@endpushOnce
