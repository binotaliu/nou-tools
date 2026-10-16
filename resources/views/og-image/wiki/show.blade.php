{{-- Social card for a wiki page; see og-image/_card.blade.php. --}}
@php
    $wikiPage = $props['viewModel']['page'];
@endphp
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => $props['viewModel']['wikiTitle'],
    'kicker' => 'NOU 小幫手'.$props['viewModel']['wikiTitle'],
    'title' => $wikiPage['title'],
    'subtitle' => \Illuminate\Support\Str::of((string) $wikiPage['description'])->squish()->limit(60),
])
