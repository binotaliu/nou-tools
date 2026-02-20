@props([
    'items' => [],
    'schedule' => null,
])

<table {{ $attributes->merge(['class' => 'w-full border-collapse']) }}>
    <thead>
        <tr class="border-b-2 border-warm-300 bg-warm-100">
            <th class="px-4 py-3 text-left font-bold text-warm-900">
                課程名稱
            </th>
            <th class="px-4 py-3 text-left font-bold text-warm-900">班級</th>
            <th
                class="px-4 py-3 text-left font-bold text-warm-900 print:hidden"
            >
                下次上課
            </th>
            <th
                class="px-4 py-3 text-left font-bold text-warm-900 print:hidden"
            >
                時間
            </th>
            <th class="px-4 py-3 text-left font-bold text-warm-900">教師</th>
            <th
                class="px-4 py-3 text-left font-bold text-warm-900 print:hidden"
            >
                <span class="sr-only">動作</span>
            </th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $item)
            @php
                $nextSchedule = $item->courseClass->schedules
                    ->filter(fn ($s) => $s->date->isToday() || $s->date->isFuture())
                    ->sortBy('date')
                    ->first();

                $displayStartTime =
                    $nextSchedule && $nextSchedule->start_time
                        ? $nextSchedule->start_time
                        : $item->courseClass->start_time;
                $displayEndTime =
                    $nextSchedule && $nextSchedule->end_time
                        ? $nextSchedule->end_time
                        : $item->courseClass->end_time;
            @endphp

            <tr class="border-b border-warm-200 hover:bg-warm-50">
                <td class="px-4 py-3 font-semibold text-warm-900">
                    {{ $item->courseClass->course->name }}
                </td>
                <td class="px-4 py-3 text-sm text-warm-800 tabular-nums">
                    <x-class-code :code="$item->courseClass->code" />
                </td>
                <td class="px-4 py-3 text-warm-800 tabular-nums print:hidden">
                    @if ($nextSchedule)
                        @php
                            $d = $nextSchedule->date;
                            $weekdayZh = ['日', '一', '二', '三', '四', '五', '六'][$d->dayOfWeek];
                        @endphp

                        {{ $d->format('n/j') }} ({{ $weekdayZh }})
                    @else
                        <span class="text-warm-500">無未來課程</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-warm-800 tabular-nums print:hidden">
                    @if ($displayStartTime)
                        {{ $displayStartTime }} ~ {{ $displayEndTime }}
                        @if ($nextSchedule && $nextSchedule->start_time)
                            <x-heroicon-o-exclamation-triangle
                                class="size-4 text-orange-600"
                                title="該次課程時間與一般時間不同"
                            />
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
                            <span
                                class="inline-flex flex-wrap items-baseline gap-1"
                            >
                                @if ($base !== '')
                                    <span class="shrink-0">{{ $base }}</span>
                                @endif

                                <span class="align-text-top text-xs">
                                    {{ $suffix }}
                                </span>
                            </span>
                        @else
                            {{ $teacher }}
                        @endif
                    @else
                            −
                    @endif
                </td>
                <td class="px-4 py-3 print:hidden">
                    <a
                        href="{{ route('course.show', $item->courseClass->course) }}"
                        class="mr-3 inline-flex items-center gap-1 font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900"
                    >
                        <x-heroicon-o-information-circle
                            class="inline size-4"
                        />
                        課程資訊
                    </a>

                    @if ($item->courseClass->link)
                        <a
                            href="{{ $item->courseClass->link }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 font-semibold text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                        >
                            <x-heroicon-o-video-camera class="inline size-4" />
                            視訊上課
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-6 text-center text-warm-600">
                    沒有課程。
                    <a
                        href="{{ route('schedules.edit', $schedule) }}"
                        class="font-semibold text-orange-600 hover:underline"
                    >
                        點擊編輯課表
                    </a>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
