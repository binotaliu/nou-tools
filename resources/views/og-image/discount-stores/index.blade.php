{{-- Social card for the discount store list; see og-image/_card.blade.php. --}}
@include('og-image._card', [
    'pattern' => "bg-[url('/images/i-like-food.svg')] bg-[length:390px_390px]",
    'eyebrow' => '空大學生優惠',
    'title' => '優惠店家',
    'subtitle' => '出示學生身分即可享有優惠・已收錄 '.count($props['viewModel']['stores'] ?? []).' 間店家',
])
