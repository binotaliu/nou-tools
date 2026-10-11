@php
    $viewModel = $props['viewModel'];
    $suffix = $viewModel['scheduleName'] ? ' - '.$viewModel['scheduleName'] : '';
@endphp
@include('seo._meta', [
    'title' => '成績計算機 - '.\Illuminate\Support\Str::toSemesterDisplay($viewModel['term']).$suffix.' - NOU 小幫手',
    'noindex' => true,
])
