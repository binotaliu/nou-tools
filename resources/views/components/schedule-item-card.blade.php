@props([
    'item' => null,
])

@if ($item)
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

    <div
        {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-warm-200 border-b-0 p-4 hover:shadow-md transition']) }}
    >
        {{-- 課程名稱 --}}
        <h3 class="mb-2 text-lg font-semibold text-warm-900">
            {{ $item->courseClass->course->name }}
        </h3>

        {{-- 班級代碼 --}}
        <div class="mb-3 flex items-center gap-2">
            <x-class-code>{{ $item->courseClass->code }}</x-class-code>

            @if ($item->courseClass->teacher_name)
                @php
                    $teacher = $item->courseClass->teacher_name;
                    $suffix = mb_substr($teacher, -2, null, 'UTF-8');
                    $base = mb_substr($teacher, 0, mb_strlen($teacher, 'UTF-8') - 2, 'UTF-8');
                @endphp

                @if ($suffix === '老師')
                    <p class="inline-flex items-baseline gap-1 text-warm-900">
                        @if ($base !== '')
                            <span class="text-sm">{{ $base }}</span>
                        @endif

                        <span class="text-xs text-warm-700">
                            {{ $suffix }}
                        </span>
                    </p>
                @else
                    <p class="text-sm text-warm-900">{{ $teacher }}</p>
                @endif
            @endif
        </div>

        {{-- 課程資訊網格 --}}
        <div class="mb-4 space-y-3">
            {{-- 下次上課 --}}
            <div>
                <p
                    class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase"
                >
                    下次上課
                </p>
                @if ($nextSchedule)
                    @php
                        $d = $nextSchedule->date;
                        $weekdayZh = ['日', '一', '二', '三', '四', '五', '六'][$d->dayOfWeek];
                    @endphp

                    <p
                        class="inline-flex items-center gap-1 font-semibold text-warm-900"
                    >
                        {{ $d->format('n/j') }} ({{ $weekdayZh }})
                        @if ($displayStartTime)
                            {{ $displayStartTime }} ~ {{ $displayEndTime }}
                            @if ($nextSchedule && $nextSchedule->start_time)
                                <x-heroicon-o-exclamation-triangle
                                    class="size-4 text-warm-500"
                                    title="該次課程時間與一般時間不同"
                                />
                            @endif
                        @endif
                    </p>
                @else
                    <p class="font-semibold text-warm-500">無未來課程</p>
                @endif
            </div>
        </div>

        {{-- 操作按鈕 --}}
        <div class="flex gap-2 border-t border-warm-100 pt-3">
            <a
                href="{{ route('course.show', $item->courseClass->course) }}"
                class="flex-1 rounded px-2 py-2 text-center text-sm font-semibold text-warm-800 underline underline-offset-4 transition hover:bg-warm-50 hover:text-warm-900"
            >
                <x-heroicon-o-information-circle class="mr-1 inline size-4" />
                課程資訊
            </a>

            @if ($item->courseClass->link)
                <a
                    href="{{ $item->courseClass->link }}"
                    target="_blank"
                    rel="noopener"
                    class="flex-1 rounded px-2 py-2 text-center text-sm font-semibold text-warm-500 underline underline-offset-4 transition hover:bg-orange-50 hover:text-warm-400"
                >
                    <x-heroicon-o-video-camera class="mr-1 inline size-4" />
                    視訊上課
                </a>
            @endif
        </div>
    </div>
@endif
