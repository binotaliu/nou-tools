{{-- Social card for the video classes page; see og-image/_card.blade.php. --}}
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => '視訊面授',
    'title' => '今日視訊面授',
    'subtitle' => '一覽今日所有視訊面授課程',
])
