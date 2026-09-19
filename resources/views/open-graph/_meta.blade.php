{{-- Shared server-rendered head tags for the open-graph/{route name} views
that app.blade.php includes into <head>. Inertia's <Head> is client-side only
(no SSR), so anything crawlers should see has to be emitted here.

Params: title (the full <title>), description, ogTitle, ogType, publishedTime,
modifiedTime, noindex, feed ['title', 'href'], jsonLd (array).

Every tag but <title> carries data-seo so resources/js/app.js can drop them on
the first client-side navigation instead of leaving the previous page's tags
behind. They deliberately have no data-inertia attribute: Inertia removes
managed head elements its <Head> components don't re-declare, which would strip
them from the rendered DOM crawlers evaluate. <title> is the one exception, as
Inertia swaps it for the <Head> title that carries the same text. --}}
@php
    $description = $description ?? '給 NOU 同學的非官方小工具：管理個人課表與學習進度';
    $ogTitle = $ogTitle ?? $title;
    $ogType = $ogType ?? 'website';
    $publishedTime = $publishedTime ?? null;
    $modifiedTime = $modifiedTime ?? null;
    $noindex = $noindex ?? false;
    $feed = $feed ?? null;
    $jsonLd = $jsonLd ?? null;
@endphp

<title>{{ $title }}</title>
<meta data-seo name="description" content="{{ $description }}" />
@if ($noindex)
    <meta data-seo name="robots" content="noindex, nofollow" />
@endif
<meta data-seo property="og:type" content="{{ $ogType }}" />
<meta data-seo property="og:site_name" content="NOU 小幫手" />
<meta data-seo property="og:locale" content="zh_TW" />
<meta data-seo property="og:title" content="{{ $ogTitle }}" />
<meta data-seo property="og:description" content="{{ $description }}" />
<meta data-seo property="og:url" content="{{ url()->current() }}" />
@if ($publishedTime)
    <meta
        data-seo
        property="article:published_time"
        content="{{ $publishedTime }}"
    />
@endif
@if ($modifiedTime)
    <meta
        data-seo
        property="article:modified_time"
        content="{{ $modifiedTime }}"
    />
@endif
<meta data-seo name="twitter:card" content="summary_large_image" />
<meta data-seo name="twitter:title" content="{{ $ogTitle }}" />
<meta data-seo name="twitter:description" content="{{ $description }}" />
@if ($feed)
    <link
        data-seo
        rel="alternate"
        type="application/atom+xml"
        title="{{ $feed['title'] }}"
        href="{{ $feed['href'] }}"
    />
@endif
@if ($jsonLd)
    <script data-seo type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}
    </script>
@endif
