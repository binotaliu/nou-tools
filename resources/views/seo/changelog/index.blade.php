@php
    $viewModel = $props['viewModel'];
    $posts = $viewModel['posts']['data'] ?? [];
@endphp
@include('seo._meta', [
    'title' => $viewModel['title'].' - NOU 小幫手',
    'ogTitle' => $viewModel['title'],
    'description' => 'NOU 小幫手的更新紀錄。',
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $viewModel['title'],
        'itemListElement' => collect($posts)
            ->values()
            ->map(fn (array $post, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => $post['url'],
                'name' => $post['title'],
            ])
            ->all(),
    ],
])
