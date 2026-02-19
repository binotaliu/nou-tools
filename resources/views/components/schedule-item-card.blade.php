@props([
    'item' => null,
])

@if ($item)
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

    <div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-warm-200 border-b-0 p-4 hover:shadow-md transition']) }}>
        <!-- 課程名稱 -->
        <h3 class="font-semibold text-lg text-warm-900 mb-2">
            {{ $item->courseClass->course->name }}
        </h3>

        <!-- 班級代碼 -->
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-block bg-warm-100 text-warm-800 text-xs font-mono px-2 py-1 rounded">
                {{ $item->courseClass->code }}
            </span>

            @if ($item->courseClass->teacher_name)
                @php
                    $teacher = $item->courseClass->teacher_name;
                    $suffix = mb_substr($teacher, -2, null, 'UTF-8');
                    $base = mb_substr($teacher, 0, mb_strlen($teacher, 'UTF-8') - 2, 'UTF-8');
                @endphp

                @if ($suffix === '老師')
                    <p class="text-warm-900 inline-flex items-baseline gap-1">
                        @if ($base !== '')
                            <span class="text-sm">{{ $base }}</span>
                        @endif
                        <span class="text-xs text-warm-700">{{ $suffix }}</span>
                    </p>
                @else
                    <p class="text-sm text-warm-900">{{ $teacher }}</p>
                @endif
            @endif
        </div>

        <!-- 課程資訊網格 -->
        <div class="space-y-3 mb-4">
            <!-- 下次上課 -->
            <div>
                <p class="text-xs font-semibold text-warm-600 uppercase tracking-wide mb-1">下次上課</p>
                @if ($nextSchedule)
                    @php
                        $d = $nextSchedule->date;
                        $weekdayZh = ['日','一','二','三','四','五','六'][$d->dayOfWeek];
                    @endphp
                    <p class="text-warm-900 font-semibold inline-flex items-center gap-1">
                        {{ $d->format('n/j') }} ({{ $weekdayZh }})
                        @if ($displayStartTime)
                                {{ $displayStartTime }} ~ {{ $displayEndTime }}
                                @if ($nextSchedule && $nextSchedule->start_time)
                                    <x-heroicon-o-exclamation-triangle class="size-4 text-orange-600" title="該次課程時間與一般時間不同" />
                                @endif
                        @endif
                    </p>
                @else
                    <p class="text-warm-500 font-semibold">無未來課程</p>
                @endif
            </div>
        </div>

        <!-- 操作按鈕 -->
        <div class="flex gap-2 pt-3 border-t border-warm-100">
            <a href="{{ route('course.show', $item->courseClass->course) }}"
                class="flex-1 text-center text-sm text-warm-800 hover:text-warm-900 font-semibold underline underline-offset-4 hover:bg-warm-50 py-2 px-2 rounded transition">
                <x-heroicon-o-information-circle class="size-4 inline mr-1" />
                課程資訊
            </a>

            @if ($item->courseClass->link)
                <a href="{{ $item->courseClass->link }}" target="_blank" rel="noopener"
                   class="flex-1 text-center text-sm text-orange-600 hover:text-orange-700 font-semibold underline underline-offset-4 hover:bg-orange-50 py-2 px-2 rounded transition">
                    <x-heroicon-o-video-camera class="size-4 inline mr-1" />
                    視訊上課
                </a>
            @endif
        </div>
    </div>
@endif
