@php
use Illuminate\Support\Str;
@endphp

# {{ $viewModel->name ?: '我的課表' }}

> 學期：{{ Str::toSemesterDisplay($viewModel->selectedTerm) }}

@if (count($viewModel->months) === 0)
本學期尚無課程資料。
@else

## 本學期選修課程

| 課程名稱 | 班級代碼 | 老師 | 面授連結 | 備用教室連結 | 課程頁面 |
| -------- | -------- | ---- | -------- | ------------ | -------- |

@foreach ($viewModel->items as $item)
| {{ $item->courseClass->course->name }} | {{ $item->courseClass->code }} | {{ $item->courseClass->teacher_name ?: '未提供' }} | {{ $item->courseClass->link ?: '未提供' }} | {{ $item->courseClass->backup_classroom_url ?: '未提供' }} | [Markdown]({{ route('course.show.md', $item->courseClass->course) }}) |
@endforeach

@foreach ($viewModel->months as $month)

## {{ $month->monthDisplay }}

@foreach ($month->dates as $date)

### {{ $date->formattedDate() }}

@foreach ($date->courses as $course)

- {{ $course->time }} {{ $course->courseName }}（{{ $course->code }}）@if ($course->hasOverride)（時間異動）@endif

@endforeach

@endforeach
@endforeach
@endif

@if (count($viewModel->exams) > 0)

## 考試資訊

@foreach ($viewModel->exams as $exam)

- {{ $exam->courseName }}@if ($exam->classCode)（{{ $exam->classCode }}）@endif：
  @if ($exam->formattedMidtermDate())期中考 {{ $exam->formattedMidtermDate() }}@endif
  @if ($exam->formattedFinalDate())期末考 {{ $exam->formattedFinalDate() }}@endif
  @if ($exam->formattedExamTime()){{ $exam->formattedExamTime() }}@endif

@endforeach
@endif

## 連結

- [完整檢視]({{ route('schedules.show', $viewModel->uuid) }}): 以網頁方式完整檢視課表
- [編輯課表]({{ route('schedules.edit', $viewModel->uuid) }}): 編輯課表資訊（網頁）
- [學習進度表]({{ route('learning-progress.show', ['schedule' => $viewModel->uuid, 'term' => $viewModel->selectedTerm]) }}): 學習進度表，可用於管理自己的學習進度與課程完成狀態

## 備註

- 所有日期都以台灣時間（UTC+8）為準，若有課程時間異動，請以學校公告為準。
- 備用連結為統一面授時若主要連結滿員時可使用之教室連結。
