@php
    $course = $props['viewModel']['course'];

    $description = $course['name'].' 是國立空中大學'
        .($course['department'] ? ' '.$course['department'] : '')
        .($course['term'] ? ' 在 '.\Illuminate\Support\Str::toSemesterDisplay($course['term']) : '')
        .' 開設的'
        .($course['credits'] ? ' '.$course['credits'].' 學分課程' : '課程');
@endphp
@include('seo._meta', [
    'title' => $course['name'].' - 檢視課程 - NOU 小幫手',
    'ogTitle' => $course['name'],
    'description' => $description,
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => $course['name'],
        'description' => $description,
        'courseCode' => (string) $course['id'],
        'provider' => ['@type' => 'CollegeOrUniversity', 'name' => '國立空中大學'],
        'url' => url()->current(),
        'inLanguage' => 'zh-Hant',
    ],
])
