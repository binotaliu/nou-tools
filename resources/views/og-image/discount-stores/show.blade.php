{{-- Social card for a single discount store; see _card.blade.php. --}}
@php
    $store = $props['viewModel'];
    $location = collect([$store['city'] ?? null, $store['district'] ?? null])->filter()->implode(' ');
@endphp
@include('og-image.discount-stores._card', [
    'eyebrow' => collect(['優惠店家', $store['categoryName'] ?? null, $location])->filter()->implode('・'),
    'kicker' => '空大學生適用優惠',
    'title' => $store['name'],
    'subtitle' => \Illuminate\Support\Str::of($store['discountDetails'])->squish()->limit(60),
])
