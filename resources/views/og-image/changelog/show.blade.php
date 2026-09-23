{{-- Social card for a changelog post; see og-image/_card.blade.php. --}}
@php
    $post = $props['viewModel']['post'];
@endphp
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => $props['viewModel']['title'],
    'title' => $post['title'],
])
