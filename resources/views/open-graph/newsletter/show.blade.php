{{-- Open Graph / Twitter tags for a newsletter issue; the og:image itself comes
from the og-image card. --}}
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

    @include('open-graph._meta', [
        'title' => $issue['title'].' - NOU 小幫手',
        'ogTitle' => $issue['title'],
        'ogType' => 'article',
        'description' => $description,
        'publishedTime' => $issue['publishedAt'],
        'feed' => ['title' => $props['viewModel']['newsletterTitle'], 'href' => $props['viewModel']['feedUrl']],
        'jsonLd' => [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $issue['title'],
            'datePublished' => $issue['publishedAt'] ?? $issue['publishesOn'],
            'isPartOf' => ['@type' => 'Periodical', 'name' => $props['viewModel']['newsletterTitle']],
        ],
    ])
@endif
