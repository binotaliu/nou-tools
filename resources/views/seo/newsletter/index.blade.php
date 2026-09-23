@php
    $viewModel = $props['viewModel'];
    $issues = $viewModel['issues']['data'] ?? [];
@endphp
@include('seo._meta', [
    'title' => $viewModel['title'].' - NOU 小幫手',
    'ogTitle' => $viewModel['title'],
    'description' => '每兩週整理一次空大各處室、學系與學習指導中心的公告，以及接下來兩週的校曆重點。',
    'feed' => ['title' => $viewModel['title'], 'href' => $viewModel['feedUrl']],
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $viewModel['title'],
        'itemListElement' => collect($issues)
            ->values()
            ->map(fn (array $issue, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => $issue['url'],
                'name' => $issue['title'],
            ])
            ->all(),
    ],
])
