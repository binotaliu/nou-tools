@php
use Illuminate\Support\Str;
@endphp

# 本學期開課表

> 學期：{{ Str::toSemesterDisplay($page->selectedTerm) }}

可透過 `term` 查詢參數選擇其他學期，可選學期：{{ implode('、', $page->availableTerms) }}。

## 一般課程

@if ($page->groups->count() === 0)
目前查無考試時間資料。
@else
@foreach ($page->groups as $group)

### {{ $group->label }}

| 課程名稱 | 學系 | 學分 |
| -------- | ---- | ---- |

@foreach ($group->courses as $course)
| [{{ $course->name }}]({{ route('course.show', $course->id) }}) | {{ $course->department ?? '未提供' }} | {{ $course->credits ?? '未提供' }} |
@endforeach
@endforeach
@endif

## 微學分與全遠距

@if ($page->microCreditOrRemoteCourses->count() === 0)
目前查無微學分或全遠距課程。
@else
| 課程名稱 | 學系 | 學分 |
| -------- | ---- | ---- |

@foreach ($page->microCreditOrRemoteCourses as $course)
| [{{ $course->name }}]({{ route('course.show', $course->id) }}) | {{ $course->department ?? '未提供' }} | {{ $course->credits ?? '未提供' }} |
@endforeach
@endif

## 連結

- [完整檢視]({{ route('course.schedule') }}): 以網頁方式完整檢視開課表
