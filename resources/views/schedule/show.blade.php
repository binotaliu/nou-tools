@extends('layouts.app')

@section('title', $schedule->name ?: '我的課表')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start mb-8 gap-y-4">
            <div>
                <h2 class="text-3xl font-bold text-warm-900 mb-2">
                    {{ $schedule->name ?: '我的課表' }}
                </h2>
                <p class="text-sm text-warm-600 mt-1 gap-1 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    小提示：將此頁加入瀏覽器書籤，下次即可快速開啟課表。
                </p>
            </div>

                <div class="flex gap-2 w-full md:w-auto" x-data="{ subscribeOpen: false }">
                    <a
                       href="{{ route('schedule.edit', $schedule) }}"
                       class="w-1/2 md:w-auto bg-white hover:bg-warm-50 text-warm border border-warm-300 font-semibold py-2 px-4 rounded-lg transition inline-flex justify-center md:justify-start items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        編輯課表
                    </a>

                @php
                    $icsUrl = route('schedule.calendar', $schedule);
                    $webcalUrl = preg_replace('/^https?/', 'webcal', $icsUrl);
                    $googleUrl = 'https://calendar.google.com/calendar/r?cid=' . urlencode($webcalUrl);
                    $outlookWebUrl = 'https://outlook.office.com/calendar/0/addfromweb?url=' . urlencode($webcalUrl);
                @endphp

                <button type="button" @click="subscribeOpen = true"
                   class="w-1/2 md:w-auto bg-warm-500 hover:bg-warm-600 border border-warm-500 text-white font-semibold py-2 px-4 rounded-lg transition inline-flex justify-center md:justify-start items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>

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

        <x-greeting class="mb-4" />

        <!-- Schedule Items - Responsive Table/Cards -->
        <x-schedule-items :items="$schedule->items" :schedule="$schedule" />

        <x-common-links />

        <!-- Schedule Calendar View -->
        @if (count($schedule->items) > 0)
            <div class="mt-8">
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
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-orange-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        表示該次面授時間與一般時間不同
                    </p>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-4">
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
                            <div class="space-y-3">
                                @foreach (collect($monthData['dates'])->sortKeys() as $dateStr => $courses)
                                    <div class="border-l-4 border-orange-500 pl-4 py-2">
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
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-orange-600">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                            </svg>
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
        <x-school-calendar :schedule-events="$scheduleEvents ?? []" :countdown-event="$countdownEvent ?? null" />

        <!-- Share Section -->
        <x-card class="mt-8">
            <h3 class="text-xl font-bold text-warm-900 mb-3">連結</h3>
            <p class="text-warm-700 mb-3">
                您可以使用以下連結來編輯或查看此課表，請妥善保管此連結。<br>
                <span class="font-semibold text-red-600">⚠️ 注意：任何擁有此連結的人都可以編輯您的課表，請勿隨意分享。</span>
            </p>
            <div class="bg-warm-50 p-3 rounded border border-warm-300 font-mono text-sm break-all text-warm-600">
                {{ url(route('schedule.show', $schedule)) }}
            </div>
        </x-card>
    </div>
@endsection
