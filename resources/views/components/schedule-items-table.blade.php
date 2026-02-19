@props([
    'items' => [],
    'schedule' => null,
])

<table {{ $attributes->merge(['class' => 'w-full border-collapse']) }}>
    <thead>
        <tr class="bg-warm-100 border-b-2 border-warm-300">
            <th class="px-4 py-3 text-left font-bold text-warm-900">課程名稱</th>
            <th class="px-4 py-3 text-left font-bold text-warm-900">班級</th>
            <th class="px-4 py-3 text-left font-bold text-warm-900">下次上課</th>
            <th class="px-4 py-3 text-left font-bold text-warm-900">時間</th>
            <th class="px-4 py-3 text-left font-bold text-warm-900">教師</th>
            <th class="px-4 py-3 text-left font-bold text-warm-900">
                <span class="sr-only">
                    動作
                </span>
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $item)
            @php
                $nextSchedule = $item->courseClass->schedules
                    ->filter(fn($s) => $s->date->isToday() || $s->date->isFuture())
                    ->sortBy('date')
                    ->first();

                $displayStartTime = $nextSchedule && $nextSchedule->start_time
                    ? $nextSchedule->start_time
                    : $item->courseClass->start_time;
                $displayEndTime = $nextSchedule && $nextSchedule->end_time
                    ? $nextSchedule->end_time
                    : $item->courseClass->end_time;
            @endphp

            <tr class="border-b border-warm-200 hover:bg-warm-50">
                <td class="px-4 py-3 font-semibold text-warm-900">
                    {{ $item->courseClass->course->name }}
                </td>
                <td class="px-4 py-3 text-warm-800 tabular-nums text-sm">
                    {{ $item->courseClass->code }}
                </td>
                <td class="px-4 py-3 text-warm-800 tabular-nums">
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
                <td class="px-4 py-3 text-warm-800 tabular-nums">
                    @if ($displayStartTime)
                        {{ $displayStartTime }} ~ {{ $displayEndTime }}
                        @if ($nextSchedule && $nextSchedule->start_time)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 inline text-orange-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        @endif
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
                            <span class="inline-flex items-baseline flex-wrap gap-1">
                                @if ($base !== '')
                                    <span class="shrink-0">{{ $base }}</span>
                                @endif
                                <span class="text-xs align-text-top">{{ $suffix }}</span>
                            </span>
                        @else
                            {{ $teacher }}
                        @endif
                    @else
                        −
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('course.show', $item->courseClass->course) }}"
                        class="text-warm-800 hover:text-warm-900 font-semibold underline underline-offset-4 mr-3 inline-flex gap-2 items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        課程資訊
                    </a>

                    @if ($item->courseClass->link)
                        <a href="{{ $item->courseClass->link }}" target="_blank" rel="noopener"
                           class="text-orange-600 hover:text-orange-700 font-semibold underline underline-offset-4 hover:no-underline inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            視訊上課
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
