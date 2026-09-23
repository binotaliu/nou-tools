@php
    $viewModel = $props['viewModel'];
    $suffix = $viewModel['scheduleName'] ? ' - '.$viewModel['scheduleName'] : '';
@endphp
@include('seo._meta', [
    'title' => '學習進度表 - '.\Illuminate\Support\Str::toSemesterDisplay($viewModel['term']).$suffix.' - NOU 小幫手',
    'noindex' => true,
])
