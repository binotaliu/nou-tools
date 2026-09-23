@php
    $stores = $props['viewModel']['stores'] ?? [];
@endphp
@include('seo._meta', [
    'title' => '優惠店家 - NOU 小幫手',
    'description' => '國立空中大學學生優惠店家列表，出示學生身分即可享有優惠，歡迎回報或新增店家資訊。',
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => '優惠店家',
        'itemListElement' => collect($stores)
            ->values()
            ->map(fn (array $store, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('discount-stores.show', $store['id']),
                'name' => $store['name'],
            ])
            ->all(),
    ],
])
