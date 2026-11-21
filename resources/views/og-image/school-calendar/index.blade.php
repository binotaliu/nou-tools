{{-- Social card for 學校行事曆; see og-image/_card.blade.php. --}}
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => '空大重要日程',
    'kicker' => $props['viewModel']['termLabel'],
    'title' => '學校行事曆',
    'subtitle' => '開課、選課、期中考、期末考與成績查詢等日期',
])
