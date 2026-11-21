{{-- Social card for 本學期開課表; see og-image/_card.blade.php. --}}
@php
    $viewModel = $props['viewModel'];
    $courseCount = collect($viewModel['groups'] ?? [])->sum(fn (array $group): int => count($group['courses'] ?? []))
        + count($viewModel['microCreditOrRemoteCourses'] ?? []);
@endphp
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => '開課資訊',
    'kicker' => \Illuminate\Support\Str::toSemesterDisplay($viewModel['selectedTerm']),
    'title' => '本學期開課表',
    'subtitle' => $courseCount > 0
        ? '依考試時間、學系或學分瀏覽・共 '.$courseCount.' 門課程'
        : '依考試時間、學系或學分瀏覽',
])
