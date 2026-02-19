@extends('layouts.app')

@section('title', ($schedule->name ?: '我的課表') . ' - NOU 小幫手')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start mb-8 gap-y-4">
            <div>
                <h2 class="text-3xl font-bold text-warm-900 mb-2">
                    {{ $schedule->name ?: '我的課表' }}
                </h2>
                <p class="text-sm text-warm-600 mt-1 gap-1 flex items-center print:hidden">
                    <x-heroicon-o-information-circle class="size-4 inline" />
                    小提示：將此頁加入瀏覽器書籤，下次即可快速開啟課表。
                </p>
            </div>

                <div class="flex gap-2 w-full md:w-auto print:hidden" x-data="{ subscribeOpen: false }">
                    <a
                       href="{{ route('schedules.edit', $schedule) }}"
                       class="w-1/2 md:w-auto bg-white hover:bg-warm-50 text-warm border border-warm-300 font-semibold py-2 px-4 rounded-lg transition inline-flex justify-center md:justify-start items-center gap-2"
                    >
                        <x-heroicon-o-pencil-square class="size-4" />
                        編輯課表
                    </a>

                @php
                    $icsUrl = route('schedules.calendar', $schedule);
                    $webcalUrl = preg_replace('/^https?/', 'webcal', $icsUrl);
                    $googleUrl = 'https://calendar.google.com/calendar/r?cid=' . urlencode($webcalUrl);
                    $outlookWebUrl = 'https://outlook.office.com/calendar/0/addfromweb?url=' . urlencode($webcalUrl);
                @endphp

                <button type="button" @click="subscribeOpen = true"
                   class="w-1/2 md:w-auto bg-warm-500 hover:bg-warm-600 border border-warm-500 text-white font-semibold py-2 px-4 rounded-lg transition inline-flex justify-center md:justify-start items-center gap-2">
                    <x-heroicon-o-calendar class="size-4 inline" />
                    訂閱行事曆
                </button>

                <!-- Subscribe modal -->
                <x-modal name="subscribeOpen" title="訂閱行事曆" description="選擇要使用的方式來訂閱或下載您的課表行事曆：">
                        <div class="grid gap-3">
                            <a href="{{ $webcalUrl }}" @click="subscribeOpen = false"
                               class="w-full inline-flex items-center justify-center gap-2 bg-white border border-warm-200 text-warm-900 py-2 px-3 rounded hover:bg-warm-50">
                                Apple 日曆 (iOS / macOS)
                            </a>

                            <a href="{{ $googleUrl }}" target="_blank" rel="noopener" @click="subscribeOpen = false"
                               class="w-full inline-flex items-center justify-center gap-2 bg-blue-50 text-warm-900 border border-blue-100 py-2 px-3 rounded hover:bg-blue-100">
                                Google 日曆
                            </a>

                            <a href="{{ $outlookWebUrl }}" target="_blank" rel="noopener" @click="subscribeOpen = false"
                               class="w-full inline-flex items-center justify-center gap-2 bg-sky-50 text-warm-900 border border-sky-100 py-2 px-3 rounded hover:bg-sky-100">
                                Microsoft 365 / Outlook.com
                            </a>

                            <a href="{{ $icsUrl }}" target="_blank" rel="noopener" download @click="subscribeOpen = false"
                               class="w-full inline-flex items-center justify-center gap-2 bg-warm-50 text-warm-900 border border-warm-200 py-2 px-3 rounded hover:bg-warm-100">
                                下載 iCal（.ics）
                            </a>
                        </div>

                        <x-slot:footer>
                            <button type="button" @click="subscribeOpen = false"
                                    class="px-4 py-2 text-sm text-warm-700 hover:underline">取消</button>
                        </x-slot:footer>
                </x-modal>
            </div>
        </div>

        <x-greeting class="mb-4 print:hidden" />

        <!-- Schedule Items - Responsive Table/Cards -->
        <x-schedule-items :items="$schedule->items" :schedule="$schedule" />

        <x-common-links class="print:hidden mb-8" />

        <!-- Schedule Calendar View -->
        @if (count($schedule->items) > 0)
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-warm-900 mb-4">面授日期</h3>
                @php
                    $hasAnyOverride = $schedule->items->contains(function ($item) {
                        return $item->courseClass->schedules->contains(function ($s) {
                            return $s->start_time !== null;
                        });
                    });
                @endphp

                @if ($hasAnyOverride)
                    <p class="text-sm text-warm-600 mb-4 flex items-center gap-1">
                        <x-heroicon-o-exclamation-triangle class="size-4 text-orange-600" />
                        表示該次面授時間與一般時間不同
                    </p>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 print:grid-cols-1 gap-x-6 gap-y-4 mb-4">
                    @php
                        $coursesByMonth = [];
                        foreach ($schedule->items as $item) {
                            foreach ($item->courseClass->schedules as $classSchedule) {
                                $monthKey = $classSchedule->date->format('Y-m');
                                $monthKey_display = $classSchedule->date->format('Y 年 n 月');
                                if (!isset($coursesByMonth[$monthKey])) {
                                    $coursesByMonth[$monthKey] = ['month' => $monthKey_display, 'dates' => []];
                                }
                                $dateKey = $classSchedule->date->format('Y-m-d');
                                if (!isset($coursesByMonth[$monthKey]['dates'][$dateKey])) {
                                    $coursesByMonth[$monthKey]['dates'][$dateKey] = [];
                                }
                                // Use schedule time override if it exists, otherwise use class default
                                $displayStartTime = $classSchedule->start_time ?? $item->courseClass->start_time;
                                $displayEndTime = $classSchedule->end_time ?? $item->courseClass->end_time;
                                $hasOverride = $classSchedule->start_time !== null;

                                $coursesByMonth[$monthKey]['dates'][$dateKey][] = [
                                    'courseName' => $item->courseClass->course->name,
                                    'code' => $item->courseClass->code,
                                    'time' => $displayStartTime ? $displayStartTime . ' - ' . $displayEndTime : '未設定',
                                    'hasOverride' => $hasOverride,
                                    'date' => $classSchedule->date,
                                ];
                            }
                        }
                    @endphp

                    @foreach (collect($coursesByMonth)->sortKeys() as $monthData)
                        <x-card>
                            <h4 class="text-xl font-bold text-warm-900 mb-4">{{ $monthData['month'] }}</h4>
                            <div class="space-y-3 grid grid-cols-1 print:grid-cols-2 gap-x-6 gap-y-1">
                                @foreach (collect($monthData['dates'])->sortKeys() as $dateStr => $courses)
                                    <div class="border-l-4 border-orange-500 pl-4 py-2 break-inside-avoid-page">
                                        @php
                                            $d = \Carbon\Carbon::parse($dateStr);
                                            $weekdayZh = ['日','一','二','三','四','五','六'][$d->dayOfWeek];
                                        @endphp
                                        <div class="font-semibold text-warm-900 mb-1">
                                            {{ $d->format('n/j') }} ({{ $weekdayZh }})
                                        </div>
                                        <div class="space-y-1">
                                            @foreach ($courses as $course)
                                                <div class="text-sm text-warm-700">
                                                    <span class="font-semibold">{{ $course['courseName'] }}</span>
                                                    <span class="text-xs text-warm-600">({{ $course['code'] }})</span><br>
                                                    <span class="text-warm-600 inline-flex items-center gap-1">
                                                        {{ $course['time'] }}
                                                        @if ($course['hasOverride'])
                                                            <x-heroicon-o-exclamation-triangle class="size-4 text-orange-600" title="該次課程時間與一般時間不同" />
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- School Calendar --}}
        <x-school-calendar
            :schedule-events="$scheduleEvents ?? []"
            :countdown-event="$countdownEvent ?? null"
            class="mb-8"
        />

        @php
            // Collect unique courses that appear in this schedule and sort by earliest exam datetime (ascending)
            $courses = $schedule->items->map(fn($it) => $it->courseClass->course)->unique('id')->values();

            $coursesWithExam = $courses->filter(fn($c) => $c->midterm_date || $c->final_date || $c->exam_time_start || $c->exam_time_end)
                ->map(function ($course) {
                    $dates = collect();

                    if ($course->midterm_date) {
                        $dt = \Illuminate\Support\Carbon::parse($course->midterm_date);
                        if ($course->exam_time_start) {
                            $dt = $dt->setTimeFromTimeString($course->exam_time_start);
                        }
                        $dates->push($dt);
                    }

                    if ($course->final_date) {
                        $dt = \Illuminate\Support\Carbon::parse($course->final_date);
                        if ($course->exam_time_start) {
                            $dt = $dt->setTimeFromTimeString($course->exam_time_start);
                        }
                        $dates->push($dt);
                    }

                    // attach earliest exam datetime (or null)
                    $course->earliest_exam_at = $dates->count() ? $dates->min() : null;

                    return $course;
                })
                ->sortBy(function ($c) {
                    return $c->earliest_exam_at ? $c->earliest_exam_at->getTimestamp() : PHP_INT_MAX;
                })->values();
        @endphp

        @if ($coursesWithExam->isNotEmpty())
            <x-card class="mb-8">
                <h3 class="text-2xl font-bold text-warm-900 mb-4">考試資訊</h3>
                <p class="text-sm text-warm-600 mb-4">以下為您加入課表的科目之期中 / 期末考試日期與節次。</p>

                <!-- 手機：卡片列表 -->
                <div class="md:hidden space-y-3">
                    @foreach ($coursesWithExam as $course)
                        @php
                            $firstClass = $schedule->items->first(fn($it) => $it->courseClass->course->id === $course->id)?->courseClass;
                        @endphp

                        <div class="bg-white rounded-lg border border-warm-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="font-semibold text-warm-900">{{ $course->name }}</div>
                                    @if ($firstClass)
                                        <div class="text-xs text-warm-600 mt-1">{{ $firstClass->code }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-warm-600 uppercase tracking-wide mb-1">期中考</p>
                                    @if ($course->midterm_date)
                                        @php
                                            $md = \Illuminate\Support\Carbon::parse($course->midterm_date);
                                            $weekday = ['日','一','二','三','四','五','六'][$md->dayOfWeek];
                                        @endphp

                                        <div class="text-warm-900 font-semibold">{{ $md->format('n/j') }} ({{ $weekday }})</div>

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div class="text-sm text-warm-600 mt-1">
                                                @if ($course->exam_time_start && $course->exam_time_end)
                                                    {{ $course->exam_time_start }} - {{ $course->exam_time_end }}
                                                @else
                                                    {{ $course->exam_time_start ?? $course->exam_time_end }}
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-warm-500">—</div>
                                    @endif
                                </div>

                                <div>
                                    <p class="text-xs font-semibold text-warm-600 uppercase tracking-wide mb-1">期末考</p>
                                    @if ($course->final_date)
                                        @php
                                            $fd = \Illuminate\Support\Carbon::parse($course->final_date);
                                            $weekday = ['日','一','二','三','四','五','六'][$fd->dayOfWeek];
                                        @endphp

                                        <div class="text-warm-900 font-semibold">{{ $fd->format('n/j') }} ({{ $weekday }})</div>

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div class="text-sm text-warm-600 mt-1">
                                                @if ($course->exam_time_start && $course->exam_time_end)
                                                    {{ $course->exam_time_start }} - {{ $course->exam_time_end }}
                                                @else
                                                    {{ $course->exam_time_start ?? $course->exam_time_end }}
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-warm-500">—</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- 桌面：維持表格，但只在 md+ 顯示 -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full border-collapse overflow-hidden rounded">
                        <thead>
                            <tr class="bg-warm-100 border-b-2 border-warm-300 rounded-t">
                                <th class="px-4 py-3 text-left font-bold text-warm-900">課程</th>
                                <th class="px-4 py-3 text-left font-bold text-warm-900">期中考</th>
                                <th class="px-4 py-3 text-left font-bold text-warm-900">期末考</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($coursesWithExam as $course)
                                @php
                                    $firstClass = $schedule->items->first(fn($it) => $it->courseClass->course->id === $course->id)?->courseClass;
                                @endphp
                                <tr class="border-b border-warm-200 hover:bg-warm-50">
                                    <td class="px-4 py-3 font-semibold text-warm-900">
                                        {{ $course->name }}
                                        @if ($firstClass)
                                            <div class="text-xs text-warm-600 mt-1">{{ $firstClass->code }}</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-warm-800 tabular-nums">
                                        @if ($course->midterm_date)
                                            @php
                                                $md = \Illuminate\Support\Carbon::parse($course->midterm_date);
                                                $weekday = ['日','一','二','三','四','五','六'][$md->dayOfWeek];
                                            @endphp

                                            <div class="font-semibold">{{ $md->format('n/j') }} ({{ $weekday }})</div>
                                        @else
                                            <div class="text-warm-500">—</div>
                                        @endif

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div class="text-sm text-warm-600 mt-1">
                                                @if ($course->exam_time_start && $course->exam_time_end)
                                                    {{ $course->exam_time_start }} - {{ $course->exam_time_end }}
                                                @else
                                                    {{ $course->exam_time_start ?? $course->exam_time_end }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-warm-800 tabular-nums">
                                        @if ($course->final_date)
                                            @php
                                                $fd = \Illuminate\Support\Carbon::parse($course->final_date);
                                                $weekday = ['日','一','二','三','四','五','六'][$fd->dayOfWeek];
                                            @endphp

                                            <div class="font-semibold">{{ $fd->format('n/j') }} ({{ $weekday }})</div>
                                        @else
                                            <div class="text-warm-500">—</div>
                                        @endif

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div class="text-sm text-warm-600 mt-1">
                                                @if ($course->exam_time_start && $course->exam_time_end)
                                                    {{ $course->exam_time_start }} - {{ $course->exam_time_end }}
                                                @else
                                                    {{ $course->exam_time_start ?? $course->exam_time_end }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif

        <!-- Share Section -->
        <x-card>
            <div class="print:flex flex items-center justify-between gap-4">
                <div class="md:flex-1 print:flex-1">
                    <p class="text-warm-700 mb-3">
                        您可以使用以下連結來編輯或檢視此課表，請妥善保管此連結。<br>
                        <span class="font-semibold text-red-600 inline-flex items-center gap-1">
                            <x-heroicon-o-exclamation-triangle class="size-4" />
                            注意：任何擁有此連結的人都可以編輯您的課表。
                        </span>
                    </p>

                    <div class="bg-warm-50 p-3 rounded border border-warm-300 font-mono text-sm break-all text-warm-600">
                        {{ url(route('schedules.show', $schedule)) }}
                    </div>
                </div>

                <div class="hidden md:flex print:flex flex-col justify-center items-center w-28">
                    <div class="bg-white p-2 rounded border border-warm-200">
                        {!! DNS2D::getBarcodeSVG(url(route('schedules.show', $schedule)), 'QRCODE') !!}
                    </div>
                </div>
            </div>
        </x-card>

        <div class="mt-6 flex justify-end print:hidden">
            <button
                type="button"
                onclick="window.print()"
                class="bg-warm-500 hover:bg-warm-600 border border-warm-500 text-white font-semibold py-2 px-4 rounded-lg transition inline-flex items-center gap-2"
            >
                <x-heroicon-o-printer class="size-4 inline" />
                列印
            </button>
        </div>
    </div>
@endsection
