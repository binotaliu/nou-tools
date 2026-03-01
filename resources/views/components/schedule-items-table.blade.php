@props([
    'items' => [],
    'scheduleUuid' => null,
])

<x-table
    {{ $attributes->merge(['class' => 'border-collapse', 'aria-describedby' => 'schedule-items-caption']) }}
>
    <caption id="schedule-items-caption" class="sr-only">
        課程時間表項目清單
    </caption>

    <x-table-head>
        <x-table-row>
            <x-table-head-column>課程名稱</x-table-head-column>
            <x-table-head-column>班級</x-table-head-column>
            <x-table-head-column class="print:hidden">
                下次上課
            </x-table-head-column>
            <x-table-head-column class="print:hidden">時間</x-table-head-column>
            <x-table-head-column>教師</x-table-head-column>
            <x-table-head-column class="print:hidden">
                <span class="sr-only">動作</span>
            </x-table-head-column>
        </x-table-row>
    </x-table-head>

    <x-table-body>
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

            <x-table-row>
                <x-table-head-column
                    scope="row"
                    class="font-semibold text-warm-900"
                >
                    {{ $item->courseClass->course->name }}
                </x-table-head-column>

                <x-table-column class="text-sm tabular-nums">
                    <x-class-code :code="$item->courseClass->code" />
                </x-table-column>

                <x-table-column class="tabular-nums print:hidden">
                    @if ($nextSchedule)
                        {!! str_replace(' ', '&nbsp;', e(Date::parse($nextSchedule->date)->isoFormat('M/D (dd)'))) !!}
                    @else
                        <span class="text-warm-500">無未來課程</span>
                    @endif
                </x-table-column>

                <x-table-column class="tabular-nums print:hidden">
                    @if ($displayStartTime)
                        {{ $displayStartTime }}&nbsp;~ {{ $displayEndTime }}
                        @if ($nextSchedule && $nextSchedule->start_time)
                            <x-heroicon-o-exclamation-triangle
                                class="size-4 text-warm-500"
                                title="該次課程時間與一般時間不同"
                                aria-hidden="true"
                            />
                            <span class="sr-only">
                                該次課程時間與一般時間不同。
                            </span>
                        @endif
                    @else
                        <span class="text-warm-400">未設定</span>
                    @endif
                </x-table-column>

                <x-table-column>
                    @if ($item->courseClass->teacher_name)
                        @php
                            $teacher = $item->courseClass->teacher_name;
                            $suffix = mb_substr($teacher, -2, null, 'UTF-8');
                            $base = mb_substr($teacher, 0, mb_strlen($teacher, 'UTF-8') - 2, 'UTF-8');
                        @endphp

                        @if ($suffix === '老師')
                            <span
                                class="inline-flex flex-wrap items-baseline gap-1"
                                aria-label="{{ $teacher }}"
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
                </x-table-column>

                <x-table-column class="print:hidden">
                    <a
                        href="{{ route('course.show', $item->courseClass->course) }}"
                        class="mr-3 inline-flex items-center gap-1 font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline"
                        aria-label="{{ $item->courseClass->course->name }} 的課程資訊"
                    >
                        <x-heroicon-o-information-circle
                            class="inline size-4"
                            aria-hidden="true"
                        />
                        課程資訊
                    </a>

                    @if ($item->courseClass->link)
                        <a
                            href="{{ $item->courseClass->link }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 font-semibold text-warm-500 underline underline-offset-4 hover:text-warm-400 hover:no-underline"
                            aria-label="前往 {{ $item->courseClass->course->name }} 的視訊上課連結"
                        >
                            <x-heroicon-o-video-camera
                                class="inline size-4"
                                aria-hidden="true"
                            />
                            視訊上課
                        </a>
                    @endif
                </x-table-column>
            </x-table-row>
        @empty
            <x-table-row>
                <x-table-column
                    colspan="6"
                    class="px-4 py-6 text-center text-warm-600"
                >
                    沒有課程。
                    <a
                        href="{{ route('schedules.edit', $schedule) }}"
                        class="font-semibold text-orange-600 hover:underline"
                    >
                        點擊編輯課表
                    </a>
                </x-table-column>
            </x-table-row>
        @endforelse
    </x-table-body>
</x-table>
