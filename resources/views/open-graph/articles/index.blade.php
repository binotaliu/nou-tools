@php
    $typeLabel = \App\Enums\ArticleType::from($props['viewModel']['type'])->label();
@endphp
@include('open-graph._meta', [
    'title' => $typeLabel.' - NOU 小幫手',
    'description' => 'NOU 小幫手整理的「'.$typeLabel.'」，提供國立空中大學同學實用的教學與資訊。',
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $typeLabel.' - NOU 小幫手',
        'url' => url()->current(),
    ],
])
