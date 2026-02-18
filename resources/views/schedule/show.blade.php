@extends('layouts.app')

@section('title', '您的課表')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-start mb-8">
            <div>
                <h2 class="text-3xl font-bold text-warm-900 mb-2">
                    {{ $schedule->name ?: '課表' }}
                </h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('schedule.edit', $schedule) }}"
                   class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                    ✏️ 編輯課表
                </a>

                @php
                    $icsUrl = route('schedule.calendar', $schedule);
                    $webcalUrl = preg_replace('/^https?/', 'webcal', $icsUrl);
                    $googleUrl = 'https://calendar.google.com/calendar/r?cid=' . urlencode($webcalUrl);
                    $outlookWebUrl = 'https://outlook.office.com/calendar/0/addfromweb?url=' . urlencode($webcalUrl);
                @endphp

                <a href="{{ $webcalUrl }}"
                   class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition">
                    📅 訂閱行事曆（推薦）
                </a>

                <div class="flex items-center gap-2">
                    <a href="{{ $googleUrl }}" target="_blank"
                       class="text-sm text-warm-700 underline hover:text-warm-900">Google</a>
                    <a href="{{ $outlookWebUrl }}" target="_blank"
                       class="text-sm text-warm-700 underline hover:text-warm-900">Microsoft 365</a>
                    <a href="{{ $icsUrl }}" target="_blank"
                       class="text-sm text-warm-700 underline hover:text-warm-900">iCal Feed</a>
                </div>
            </div>
        </div>

        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 rounded">
            <p class="font-semibold mb-2">💡 如何訂閱（建議採用）</p>
            <ul class="list-disc list-inside space-y-1 text-sm">
                <li><strong>Apple Calendar / Outlook（桌面）：</strong>點擊「訂閱行事曆」的 <code class="font-mono">webcal://</code> 連結 — 會在行事曆應用中建立訂閱（編輯後會同步）</li>
                <li><strong>Google Calendar：</strong>點選下方的 <a href="{{ $googleUrl }}" target="_blank" class="underline">加入 Google 日曆</a>（或手動新增網址：<code class="font-mono">{{ $webcalUrl }}</code>）</li>
                <li><strong>Microsoft 365：</strong>使用此連結： <a href="{{ $outlookWebUrl }}" target="_blank" class="underline">從網路新增到 Outlook</a></li>
                <li><strong>iCal Feed（原始）：</strong>直接使用 <a href="{{ $icsUrl }}" target="_blank" class="underline">ICS 連結</a>（某些裝置會把它當成訂閱）</li>
            </ul>
        </div>

        <!-- Schedule Table -->
        <div class="bg-white rounded-lg border border-warm-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-warm-100 border-b-2 border-warm-300">
                            <th class="px-4 py-3 text-left font-bold text-warm-900">課程名稱</th>
                            <th class="px-4 py-3 text-left font-bold text-warm-900">班級</th>
                            <th class="px-4 py-3 text-left font-bold text-warm-900">下次上課</th>
                            <th class="px-4 py-3 text-left font-bold text-warm-900">時間</th>
                            <th class="px-4 py-3 text-left font-bold text-warm-900">教師</th>
                            <th class="px-4 py-3 text-left font-bold text-warm-900">動作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($schedule->items as $item)
                            <tr class="border-b border-warm-200 hover:bg-warm-50">
                                <td class="px-4 py-3 font-semibold text-warm-900">
                                    {{ $item->courseClass->course->name }}
                                </td>
                                <td class="px-4 py-3 text-warm-800">
                                    {{ $item->courseClass->code }}
                                </td>
                                <td class="px-4 py-3 text-warm-800">
                                    @php
                                        $nextSchedule = $item->courseClass->schedules
                                            ->filter(fn($s) => $s->date->isToday() || $s->date->isFuture())
                                            ->sortBy('date')
                                            ->first();
                                    @endphp

                                    @if ($nextSchedule)
                                        @php
                                            $d = $nextSchedule->date;
                                            $weekdayZh = ['日','一','二','三','四','五','六'][$d->dayOfWeek];
                                        @endphp
                                        {{ $d->format('n/j') }} ({{ $weekdayZh }})
                                    @else
                                        <span class="text-warm-500">無未來課程</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-warm-800">
                                    @if ($item->courseClass->start_time)
                                        {{ $item->courseClass->start_time }} ~ {{ $item->courseClass->end_time }}
                                    @else
                                        <span class="text-warm-500">未設定</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-warm-800">
                                    @if ($item->courseClass->teacher_name)
                                        @php
                                            $teacher = $item->courseClass->teacher_name;
                                            $suffix = mb_substr($teacher, -2, null, 'UTF-8');
                                            $base = mb_substr($teacher, 0, mb_strlen($teacher, 'UTF-8') - 2, 'UTF-8');
                                        @endphp

                                        @if ($suffix === '老師')
                                            <span class="inline-flex items-baseline">
                                                @if ($base !== '')
                                                    <span>{{ $base }}</span>
                                                @endif
                                                <span class="text-xs align-text-top ml-1">{{ $suffix }}</span>
                                            </span>
                                        @else
                                            {{ $teacher }}
                                        @endif
                                    @else
                                        −
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($item->courseClass->link)
                                        <a href="{{ $item->courseClass->link }}" target="_blank"
                                           class="text-orange-600 hover:text-orange-700 font-semibold underline underline-offset-4 hover:no-underline">
                                            📎 視訊上課
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-warm-600">
                                    沒有課程。<a href="{{ route('schedule.edit', $schedule) }}"
                                               class="text-orange-600 hover:underline font-semibold">點擊編輯課表</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Schedule Calendar View -->
        @if (count($schedule->items) > 0)
            <div class="mt-8">
                <h3 class="text-2xl font-bold text-warm-900 mb-4">課程日期</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $coursesByMonth = [];
                        foreach ($schedule->items as $item) {
                            foreach ($item->courseClass->schedules as $classSchedule) {
                                $monthKey = $classSchedule->date->format('Y-m');
                                $monthKey_display = $classSchedule->date->format('Y年m月');
                                if (!isset($coursesByMonth[$monthKey])) {
                                    $coursesByMonth[$monthKey] = ['month' => $monthKey_display, 'dates' => []];
                                }
                                $dateKey = $classSchedule->date->format('Y-m-d');
                                if (!isset($coursesByMonth[$monthKey]['dates'][$dateKey])) {
                                    $coursesByMonth[$monthKey]['dates'][$dateKey] = [];
                                }
                                $coursesByMonth[$monthKey]['dates'][$dateKey][] = [
                                    'courseName' => $item->courseClass->course->name,
                                    'code' => $item->courseClass->code,
                                    'time' => $item->courseClass->start_time ? $item->courseClass->start_time . ' - ' . $item->courseClass->end_time : '未設定',
                                    'date' => $classSchedule->date,
                                ];
                            }
                        }
                    @endphp

                    @foreach (collect($coursesByMonth)->sortKeys() as $monthData)
                        <div class="bg-white rounded-lg border border-warm-200 p-6">
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
                                                    <span class="text-warm-600">{{ $course['time'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Share Section -->
        <div class="mt-8 bg-warm-50 rounded-lg border border-warm-200 p-6">
            <h3 class="text-xl font-bold text-warm-900 mb-3">連結</h3>
            <p class="text-warm-700 mb-3">
                您可以使用以下連結來編輯或查看此課表，請妥善保管此連結。<br>
                <span class="font-semibold text-red-600">⚠️ 注意：任何擁有此連結的人都可以編輯您的課表，請勿隨意分享。</span>
            </p>
            <div class="bg-white p-3 rounded border border-warm-300 font-mono text-sm break-all text-warm-600">
                {{ url(route('schedule.show', $schedule)) }}
            </div>
        </div>

        <div class="mt-6 mb-8 flex justify-center gap-4">
            <a href="{{ route('schedule.index') }}" class="bg-warm-200 hover:bg-warm-300 text-warm-900 font-semibold py-3 px-6 rounded-lg transition">
                ← 返回首頁
            </a>
            <a href="{{ route('schedule.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-6 rounded-lg transition">
                ➕ 建立新課表
            </a>
        </div>
    </div>
@endsection
