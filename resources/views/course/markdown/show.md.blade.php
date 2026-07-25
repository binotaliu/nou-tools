@php
use App\Enums\CourseClassType;
use Illuminate\Support\Str;
@endphp

# {{ $course->name }}

@if (! empty($course->term))

> 學期：{{ Str::toSemesterDisplay($course->term) }}
> @endif

## 課程資訊

| 項目 | 內容 |
| ---- | ---- |

@if ($course->credit_type)
| 必/選修 | {{ $course->credit_type }} |
@endif
@if ($course->credits)
| 學分 | {{ $course->credits }} 學分 |
@endif
@if ($course->department)
| 學系 | {{ $course->department }} |
@endif
@if ($course->in_person_class_type)
| 面授類別 | {{ $course->in_person_class_type }} |
@endif
@if ($course->media)
| 媒體 | {{ $course->media }} |
@endif
@if ($course->nature)
| 課程性質 | {{ $course->nature }} |
@endif
@if ($course->description_url)
| 科目內容 | [檢視詳細內容]({{ $course->description_url }}) |
@endif
@if ($course->multimedia_url)
| 多媒體簡介 | [檢視簡介]({{ $course->multimedia_url }}) |
@endif

@if ($course->midterm_date || $course->final_date || $course->exam_time_start || $course->exam_time_end)

## 考試資訊

@if ($course->midterm_date)

- 期中考：{{ Date::parse($course->midterm_date)->isoFormat('M/D (dd)') }}
  @if ($course->exam_time_start || $course->exam_time_end)
  {{ $course->exam_time_start ?? '' }}{{ $course->exam_time_start && $course->exam_time_end ? ' - ' : '' }}{{ $course->exam_time_end ?? '' }}
@endif
@endif
@if ($course->final_date)
- 期末考：{{ Date::parse($course->final_date)->isoFormat('M/D (dd)') }}
  @if ($course->exam_time_start || $course->exam_time_end)
  {{ $course->exam_time_start ?? '' }}{{ $course->exam_time_start && $course->exam_time_end ? ' - ' : '' }}{{ $course->exam_time_end ?? '' }}
  @endif
  @endif
  @endif

@if ($course->textbook !== null)

## 教科書資訊

| 項目 | 內容                                |
| ---- | ----------------------------------- |
| 書名 | {{ $course->textbook->book_title }} |

@if ($course->textbook->edition)
| 版本 | {{ $course->textbook->edition }} |
@endif
@if ($course->textbook->price_info)
| 價格 | {{ $course->textbook->price_info }} |
@endif
@if ($course->textbook->reference_url)
| 參考連結 | [開啟]({{ $course->textbook->reference_url }}) |
@endif
@endif

@if ($course->classes->isNotEmpty())

## 視訊面授班級與上課時間

| 班級代碼 | 類別 | 老師 | 上課時間 | 面授連結 | 備用教室連結 |
| -------- | ---- | ---- | -------- | -------- | ------------ |

@foreach ($course->classes as $class)
| {{ $class->code }} | {{ $class->type instanceof CourseClassType ? $class->type->label() : $class->type }} | {{ $class->teacher_name ?: '未提供' }} | @if ($class->start_time){{ $class->start_time }} - {{ $class->end_time }}@else 未提供 @endif | {{ $class->link ?: '未提供' }} | {{ $class->backup_classroom_url ?: '未提供' }} |
@endforeach

@foreach ($course->classes as $class)
@if ($class->schedules->isNotEmpty())

### {{ $class->code }} 視訊面授日期

@foreach ($class->schedules->sortBy('date') as $schedule)

- {{ Date::parse($schedule->date)->isoFormat('M/D (dd)') }}@if ($schedule->start_time || $schedule->end_time) {{ $schedule->start_time }} - {{ $schedule->end_time }}@endif
  @endforeach

@endif
@endforeach
@endif

@if ($previousExams->isNotEmpty())

## 考古題

| 學期 | 期中考正參 | 期中考副參 | 期末考正參 | 期末考副參 |
| ---- | ---------- | ---------- | ---------- | ---------- |

@foreach ($previousExams as $exam)
| {{ $exam->term ?? '-' }} | {{ $exam->midterm_reference_primary ?: '-' }} | {{ $exam->midterm_reference_secondary ?: '-' }} | {{ $exam->final_reference_primary ?: '-' }} | {{ $exam->final_reference_secondary ?: '-' }} |
@endforeach
@endif

## 連結

- [完整檢視]({{ route('course.show', $course) }}): 以網頁方式完整檢視課程

## 備註

- 課程資料來自國立空中大學之公開資料，基於合理使用原則，以非商用、公開的方式供其他上課同學參考使用，資料版權屬於國立空中大學所有。
