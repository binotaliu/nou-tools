@php
    $store = $props['viewModel'];
@endphp
@include('seo._meta', [
    'title' => $store['name'].' - 優惠店家 - NOU 小幫手',
    'description' => $store['seoDescription'],
])
