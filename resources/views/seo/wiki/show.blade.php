@php
    $wikiPage = $props['viewModel']['page'];
    $description = $wikiPage['description'] ?: \Illuminate\Support\Str::of(strip_tags($wikiPage['content']))->squish()->limit(120)->toString();
@endphp
@include('seo._meta', [
    'title' => $wikiPage['title'].' - '.$props['viewModel']['wikiTitle'].' - NOU 小幫手',
    'ogTitle' => $wikiPage['title'],
    'ogType' => 'article',
    'description' => $description,
    'modifiedTime' => $wikiPage['updatedAt'],
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $wikiPage['title'],
        'description' => $description,
        'dateModified' => $wikiPage['updatedAt'],
        'mainEntityOfPage' => $wikiPage['url'],
    ],
])
