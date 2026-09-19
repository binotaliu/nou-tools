{{-- Social card for a single article (manual / knowledge base); see og-image/_card.blade.php. --}}
@php
    $article = $props['viewModel']['article'];
    $typeLabel = \App\Enums\ArticleType::from($article['type'])->label();
@endphp
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => $typeLabel,
    'kicker' => 'NOU 小幫手'.$typeLabel,
    'title' => $article['title'],
    'subtitle' => \Illuminate\Support\Str::of($article['description'])->squish()->limit(60),
])
