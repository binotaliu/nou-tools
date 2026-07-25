@php
use Illuminate\Support\Str;
@endphp

# {{ $course->name }}

@if (! empty($course->term))

> 學期：{{ Str::toSemesterDisplay($course->term) }}
> @endif

## 課程資訊

| 項目 | 內容 |
| ---- | ---- |

@if ($course->creditType)
| 必/選修 | {{ $course->creditType }} |
@endif
@if ($course->credits)
| 學分 | {{ $course->credits }} 學分 |
@endif
@if ($course->department)
| 學系 | {{ $course->department }} |
@endif
@if ($inPersonClassType)
| 面授類別 | {{ $inPersonClassType }} |
@endif
@if ($media)
| 媒體 | {{ $media }} |
@endif
@if ($course->nature)
| 課程性質 | {{ $course->nature }} |
@endif
@if ($course->descriptionUrl)
| 科目內容 | [檢視詳細內容]({{ $course->descriptionUrl }}) |
@endif
@if ($multimediaUrl)
| 多媒體簡介 | [檢視簡介]({{ $multimediaUrl }}) |
@endif

@if ($course->midtermDate || $course->finalDate || $course->examTimeStart || $course->examTimeEnd)

## 考試資訊

@if ($course->midtermDate)

- 期中考：{{ Date::parse($course->midtermDate)->isoFormat('M/D (dd)') }}
  @if ($course->examTimeStart || $course->examTimeEnd)
  {{ $course->examTimeStart ?? '' }}{{ $course->examTimeStart && $course->examTimeEnd ? ' - ' : '' }}{{ $course->examTimeEnd ?? '' }}
@endif
@endif
@if ($course->finalDate)
- 期末考：{{ Date::parse($course->finalDate)->isoFormat('M/D (dd)') }}
  @if ($course->examTimeStart || $course->examTimeEnd)
  {{ $course->examTimeStart ?? '' }}{{ $course->examTimeStart && $course->examTimeEnd ? ' - ' : '' }}{{ $course->examTimeEnd ?? '' }}
  @endif
  @endif
  @endif

@if ($course->textbook !== null)

## 教科書資訊

| 項目 | 內容                               |
| ---- | ---------------------------------- |
| 書名 | {{ $course->textbook->bookTitle }} |

@if ($course->textbook->edition)
| 版本 | {{ $course->textbook->edition }} |
@endif
@if ($course->textbook->priceInfo)
| 價格 | {{ $course->textbook->priceInfo }} |
@endif
@if ($course->textbook->referenceUrl)
| 參考連結 | [開啟]({{ $course->textbook->referenceUrl }}) |
@endif
@endif

@if ($course->classes->count() > 0)

## 視訊面授班級與上課時間

| 班級代碼 | 類別 | 老師 | 上課時間 | 面授連結 | 備用教室連結 |
| -------- | ---- | ---- | -------- | -------- | ------------ |

@foreach ($course->classes as $class)
| {{ $class->code }} | {{ $class->typeLabel }} | {{ $class->teacherName ?: '未提供' }} | @if ($class->startTime){{ $class->startTime }} - {{ $class->endTime }}@else 未提供 @endif | {{ $class->link ?: '未提供' }} | {{ $class->backupClassroomUrl ?: '未提供' }} |
@endforeach

@foreach ($course->classes as $class)
@if ($class->sessions->count() > 0)

### {{ $class->code }} 視訊面授日期

@foreach ($class->sessions->toCollection()->sortBy('date') as $session)

- {{ Date::parse($session->date)->isoFormat('M/D (dd)') }}@if ($session->startTime || $session->endTime) {{ $session->startTime }} - {{ $session->endTime }}@endif
  @endforeach

@endif
@endforeach
@endif

@if ($previousSchedule && $course->previousExams->count() > 0)

## 考古題

| 學期 | 期中考正參 | 期中考副參 | 期末考正參 | 期末考副參 |
| ---- | ---------- | ---------- | ---------- | ---------- |

@foreach ($course->previousExams as $exam)
| {{ $exam->term ?? '-' }} | {{ $exam->midtermReferencePrimary ?: '-' }} | {{ $exam->midtermReferenceSecondary ?: '-' }} | {{ $exam->finalReferencePrimary ?: '-' }} | {{ $exam->finalReferenceSecondary ?: '-' }} |
@endforeach
@endif

## 連結

- [完整檢視]({{ route('course.show', $course->id) }}): 以網頁方式完整檢視課程

## 備註

- 課程資料來自國立空中大學之公開資料，基於合理使用原則，以非商用、公開的方式供其他上課同學參考使用，資料版權屬於國立空中大學所有。
