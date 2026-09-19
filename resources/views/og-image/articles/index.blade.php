{{-- Social card for an article list (manual / knowledge base); see og-image/_card.blade.php. --}}
@php
    $typeLabel = \App\Enums\ArticleType::from($props['viewModel']['type'])->label();
@endphp
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => 'NOU 小幫手',
    'title' => $typeLabel,
    'subtitle' => '國立空中大學同學實用的教學與資訊',
])
