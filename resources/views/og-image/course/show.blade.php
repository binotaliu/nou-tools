{{-- Social card for a single course; see og-image/_card.blade.php. --}}
@php
    $course = $props['viewModel']['course'];
@endphp
@include('og-image._card', [
    'pattern' => "bg-[url('/images/plus.svg')] bg-[length:90px_90px]",
    'eyebrow' => collect(['課程', $course['term'] ? \Illuminate\Support\Str::toSemesterDisplay($course['term']) : null])->filter()->implode('・'),
    'kicker' => '空中大學'.$course['department'].'課程',
    'title' => $course['name'],
    'subtitle' => collect([
        $course['credits'] ? $course['credits'].' 學分' : null,
        $props['viewModel']['media'] ? $props['viewModel']['media'].'課程' : null,
    ])->filter()->implode('・'),
])
