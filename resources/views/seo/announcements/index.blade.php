@php
    $announcements = $props['viewModel']['announcements']['data'] ?? [];
@endphp
@include('seo._meta', [
    'title' => '學校公告 - NOU 小幫手',
    'description' => '彙整國立空中大學校內公告，依發布時間排序，掌握教務、學務與各學習指導中心的最新消息。',
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => '學校公告',
        'itemListElement' => collect($announcements)
            ->values()
            ->map(fn (array $announcement, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => $announcement['url'],
                'name' => $announcement['title'],
            ])
            ->all(),
    ],
])
