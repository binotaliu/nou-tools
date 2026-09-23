@php
    $viewModel = $props['viewModel'];
    $linkItems = collect($viewModel['linkGroups'] ?? [])->flatMap(fn (array $group) => $group['links'] ?? []);
    $centerItems = collect($viewModel['centerGroup']['centers'] ?? []);
@endphp
@include('seo._meta', [
    'title' => '連結 / 學習指導中心目錄 - NOU 小幫手',
    'description' => '彙整國立空中大學校內各處室、學系與各地學習指導中心的官方網站連結。',
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => '連結 / 學習指導中心目錄',
        'itemListElement' => $linkItems->merge($centerItems)
            ->values()
            ->map(fn (array $link, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => $link['url'],
                'name' => $link['name'],
            ])
            ->all(),
    ],
])
