@include('seo._meta', [
    'title' => $props['viewModel']['title'].' - NOU 小幫手',
    'description' => '由空大同學一起整理、AI 協助編輯的知識庫：考試、選課、學分與校園生活的實用資訊。',
    'noindex' => $props['viewModel']['searchResults'] !== null,
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $props['viewModel']['title'].' - NOU 小幫手',
        'url' => url()->current(),
    ],
])
