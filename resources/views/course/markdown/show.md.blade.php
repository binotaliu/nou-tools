@php
use Illuminate\Support\Str;
@endphp

# {{ $viewModel->course->name }}

@if (! empty($viewModel->course->term))

> 學期：{{ Str::toSemesterDisplay($viewModel->course->term) }}
> @endif

## 課程資訊

| 項目 | 內容 |
| ---- | ---- |

@if ($viewModel->course->creditType)
| 必/選修 | {{ $viewModel->course->creditType }} |
@endif
@if ($viewModel->course->credits)
| 學分 | {{ $viewModel->course->credits }} 學分 |
@endif
@if ($viewModel->course->department)
| 學系 | {{ $viewModel->course->department }} |
@endif
@if ($viewModel->inPersonClassType)
| 面授類別 | {{ $viewModel->inPersonClassType }} |
@endif
@if ($viewModel->media)
| 媒體 | {{ $viewModel->media }} |
@endif
@if ($viewModel->course->nature)
| 課程性質 | {{ $viewModel->course->nature }} |
@endif
@if ($viewModel->course->descriptionUrl)
| 科目內容 | [檢視詳細內容]({{ $viewModel->course->descriptionUrl }}) |
@endif
@if ($viewModel->multimediaUrl)
| 多媒體簡介 | [檢視簡介]({{ $viewModel->multimediaUrl }}) |
@endif

@if ($viewModel->course->midtermDate || $viewModel->course->finalDate || $viewModel->course->examTimeStart || $viewModel->course->examTimeEnd)

## 考試資訊

@if ($viewModel->course->midtermDate)

- 期中考：{{ Date::parse($viewModel->course->midtermDate)->isoFormat('M/D (dd)') }}
  @if ($viewModel->course->examTimeStart || $viewModel->course->examTimeEnd)
  {{ $viewModel->course->examTimeStart ?? '' }}{{ $viewModel->course->examTimeStart && $viewModel->course->examTimeEnd ? ' - ' : '' }}{{ $viewModel->course->examTimeEnd ?? '' }}
@endif
@endif
@if ($viewModel->course->finalDate)
- 期末考：{{ Date::parse($viewModel->course->finalDate)->isoFormat('M/D (dd)') }}
  @if ($viewModel->course->examTimeStart || $viewModel->course->examTimeEnd)
  {{ $viewModel->course->examTimeStart ?? '' }}{{ $viewModel->course->examTimeStart && $viewModel->course->examTimeEnd ? ' - ' : '' }}{{ $viewModel->course->examTimeEnd ?? '' }}
  @endif
  @endif
  @endif

@if ($viewModel->course->textbook !== null)

## 教科書資訊

| 項目 | 內容                                          |
| ---- | --------------------------------------------- |
| 書名 | {{ $viewModel->course->textbook->bookTitle }} |

@if ($viewModel->course->textbook->edition)
| 版本 | {{ $viewModel->course->textbook->edition }} |
@endif
@if ($viewModel->course->textbook->priceInfo)
| 價格 | {{ $viewModel->course->textbook->priceInfo }} |
@endif
@if ($viewModel->course->textbook->referenceUrl)
| 參考連結 | [開啟]({{ $viewModel->course->textbook->referenceUrl }}) |
@endif
@endif

@if ($viewModel->course->classes->count() > 0)

## 視訊面授班級與上課時間

| 班級代碼 | 類別 | 老師 | 上課時間 | 面授連結 | 備用教室連結 |
| -------- | ---- | ---- | -------- | -------- | ------------ |

@foreach ($viewModel->course->classes as $class)
| {{ $class->code }} | {{ $class->typeLabel }} | {{ $class->teacherName ?: '未提供' }} | @if ($class->startTime){{ $class->startTime }} - {{ $class->endTime }}@else 未提供 @endif | {{ $class->link ?: '未提供' }} | {{ $class->backupClassroomUrl ?: '未提供' }} |
@endforeach

@foreach ($viewModel->course->classes as $class)
@if ($class->sessions->count() > 0)

### {{ $class->code }} 視訊面授日期

@foreach ($class->sessions->toCollection()->sortBy('date') as $session)

- {{ Date::parse($session->date)->isoFormat('M/D (dd)') }}@if ($session->startTime || $session->endTime) {{ $session->startTime }} - {{ $session->endTime }}@endif
  @endforeach

@endif
@endforeach
@endif

@if ($viewModel->previousSchedule && $viewModel->course->previousExams->count() > 0)

## 考古題

| 學期 | 期中考正參 | 期中考副參 | 期末考正參 | 期末考副參 |
| ---- | ---------- | ---------- | ---------- | ---------- |

@foreach ($viewModel->course->previousExams as $exam)
| {{ $exam->term ?? '-' }} | {{ $exam->midtermReferencePrimary ?: '-' }} | {{ $exam->midtermReferenceSecondary ?: '-' }} | {{ $exam->finalReferencePrimary ?: '-' }} | {{ $exam->finalReferenceSecondary ?: '-' }} |
@endforeach
@endif

## 連結

- [完整檢視]({{ route('course.show', $viewModel->course->id) }}): 以網頁方式完整檢視課程

## 備註

- 課程資料來自國立空中大學之公開資料，基於合理使用原則，以非商用、公開的方式供其他上課同學參考使用，資料版權屬於國立空中大學所有。
