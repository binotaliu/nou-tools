{{-- Open Graph / Twitter tags for a newsletter issue. Included into <head> by
app.blade.php (Inertia's <Head> is client-side only, so crawlers would never
see tags set there); the og:image itself comes from the og-image card. --}}
@php
    $issue = $props['viewModel']['issue'] ?? null;
@endphp
@if ($issue)
    @php
        $description = \Illuminate\Support\Str::of(html_entity_decode(strip_tags($issue['highlightsIntro']), ENT_QUOTES | ENT_HTML5))
            ->squish()
            ->limit(150)
            ->toString();

        if ($description === '') {
            $description = $props['viewModel']['newsletterTitle'].' '.$issue['issueKey'].'：國立空中大學重要消息、藝文活動與校園行事曆整理。';
        }
    @endphp

    <meta name="description" content="{{ $description }}" />
    <meta property="og:type" content="article" />
    <meta property="og:site_name" content="NOU 小幫手" />
    <meta property="og:locale" content="zh_TW" />
    <meta property="og:title" content="{{ $issue['title'] }}" />
    <meta property="og:description" content="{{ $description }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    @if ($issue['publishedAt'])
        <meta
            property="article:published_time"
            content="{{ $issue['publishedAt'] }}"
        />
    @endif
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $issue['title'] }}" />
    <meta name="twitter:description" content="{{ $description }}" />
@endif
