@php
    $article = $props['viewModel']['article'];
    $typeLabel = \App\Enums\ArticleType::from($article['type'])->label();
    $title = $article['title'].' - '.$typeLabel.' - NOU 小幫手';
@endphp
@include('open-graph._meta', [
    'title' => $title,
    'ogTitle' => $article['title'],
    'ogType' => 'article',
    'description' => $article['description'],
    'publishedTime' => $article['publishedAt'],
    'modifiedTime' => $article['updatedAt'],
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article['title'],
        'description' => $article['description'],
        'author' => ['@type' => 'Organization', 'name' => $article['author']],
        'datePublished' => $article['publishedAt'],
        'dateModified' => $article['updatedAt'] ?? $article['publishedAt'],
        'mainEntityOfPage' => url()->current(),
    ],
])
