{{-- Open Graph / Twitter tags for a changelog post; the og:image itself comes
from the og-image card. --}}
@php
    $post = $props['viewModel']['post'] ?? null;
@endphp
@if ($post)
    @php
        $description = \Illuminate\Support\Str::of(html_entity_decode(strip_tags($post['bodyHtml']), ENT_QUOTES | ENT_HTML5))
            ->squish()
            ->limit(150)
            ->toString();

        if ($description === '') {
            $description = $props['viewModel']['title'].'：NOU 小幫手的更新紀錄。';
        }
    @endphp

    @include('seo._meta', [
        'title' => $post['title'].' - NOU 小幫手',
        'ogTitle' => $post['title'],
        'ogType' => 'article',
        'description' => $description,
        'publishedTime' => $post['publishedAt'],
        'jsonLd' => [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'datePublished' => $post['publishedAt'],
            'isPartOf' => ['@type' => 'Blog', 'name' => $props['viewModel']['title']],
        ],
    ])
@endif
