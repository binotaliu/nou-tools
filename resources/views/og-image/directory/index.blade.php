{{-- Social card for the link / learning centre directory; see og-image/_card.blade.php. --}}
@php
    $centerCount = count($props['viewModel']['centerGroup']['centers'] ?? []);
@endphp
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => '連結目錄',
    'title' => '連結 / 學習指導中心目錄',
    'subtitle' => $centerCount > 0
        ? '校內處室、學系與 '.$centerCount.' 個學習指導中心的官方連結'
        : '校內處室、學系與各地學習指導中心的官方連結',
])
