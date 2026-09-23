# 今日視訊面授

> {{ $viewModel->selectedDate }} 的視訊面授課程（時間為臺北時間 UTC+8）。可用 `?date=YYYY-MM-DD` 查詢其他日期。

@forelse ($viewModel->courses as $course)

## {{ $course->name }}

@foreach ($course->classes as $class)

- {{ $class->code }}（{{ $class->typeLabel }}）：{{ $class->sessions[0]->startTime ?? $class->startTime ?? '時間未定' }}@if (($class->sessions[0]->endTime ?? $class->endTime) !== null) - {{ $class->sessions[0]->endTime ?? $class->endTime }}@endif{{ $class->teacherName ? '｜'.$class->teacherName : '' }}{{ $class->link ? '｜'.$class->link : '' }}

@endforeach
@empty
當日無視訊面授課程。
@endforelse
