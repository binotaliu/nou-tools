<x-layout>
    <div class="space-y-8">
        <x-greeting />

        <div
            class="flex flex-col gap-4 md:flex-row md:items-stretch md:justify-between"
        >
            <x-card title="功能選單">
                <x-link-button
                    :href="route('schedules.create')"
                    variant="warm-dark"
                    full-width
                    data-analytics-event="schedule_create_start"
                    data-analytics-feature="schedule"
                >
                    <x-heroicon-o-table-cells class="size-4" />

                    建立我的課表
                </x-link-button>

                <x-link-button
                    :href="route('announcements.index')"
                    variant="secondary"
                    full-width
                    class="mt-3"
                >
                    <x-heroicon-o-megaphone class="size-4" />

                    檢視學校公告
                </x-link-button>

                @if (isset($previousSchedule))
                    <div class="mt-3 w-full text-sm text-warm-600">
                        <x-link-button
                            :href="route('schedules.show', $previousSchedule->token)"
                            variant="secondary"
                            full-width
                            class="text-center text-warm-700"
                            data-analytics-event="schedule_open_previous"
                            data-analytics-feature="schedule"
                        >
                            <div
                                class="max-w-xs truncate font-medium text-warm-800"
                            >
                                {{ $previousSchedule->name ?? '（未命名）' }}
                            </div>
                        </x-link-button>
                    </div>
                @endif
            </x-card>

            <x-common-links />
        </div>

        {{-- School Calendar --}}
        <x-school-calendar />

        {{-- 今日面授 --}}
        <x-card x-data="{ date: '{{ $selectedDate }}' }" title="今日視訊面授">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <label
                        for="video-course-date"
                        class="text-sm text-warm-500"
                    >
                        選擇日期
                    </label>
                    <input
                        type="date"
                        id="video-course-date"
                        class="rounded border px-3 py-1 text-sm"
                        x-model="date"
                        @change="window.location = `?date=${date}`"
                        :value="date"
                    />
                </div>
            </div>

            <div class="mt-4 space-y-6">
                @if ($courses->isEmpty())
                    <div
                        class="flex min-h-64 items-center justify-center gap-x-2 text-2xl text-warm-500"
                    >
                        <x-heroicon-o-face-smile class="size-8" />
                        今日無面授課程
                    </div>
                @else
                    @foreach ($courses as $course)
                        <div>
                            <h4 class="mb-3 font-semibold text-warm-800">
                                {{ $course->name }}
                            </h4>
                            <div
                                class="ml-2 grid grid-cols-1 gap-2 space-y-2 md:grid-cols-2 lg:grid-cols-3"
                            >
                                @php
                                    $typeLabels = [
                                        'morning' => '上午班',
                                        'afternoon' => '下午班',
                                        'evening' => '夜間班',
                                        'full_remote' => '全遠距',
                                        'micro_credit' => '微學分',
                                        'other' => '其他',
                                    ];
                                    $grouped = $course->classes->groupBy(fn ($class) => in_array($class->type->value, array_keys($typeLabels)) ? $class->type : 'other');
                                @endphp

                                @foreach ($typeLabels as $typeKey => $label)
                                    @if (isset($grouped[$typeKey]) && $grouped[$typeKey]->isNotEmpty())
                                        <div
                                            class="flex flex-col items-stretch gap-2"
                                        >
                                            <div
                                                class="text-sm font-semibold text-warm-700"
                                            >
                                                {{ $label }}
                                            </div>

                                            @php
                                                // group classes by start/end time so we show the time once per time slot
                                                // If there's a schedule override for today, use that instead
                                                $timeGroups = $grouped[$typeKey]->groupBy(function ($c) use ($selectedDate) {
                                                    $todaySchedule = $c->schedules->first();
                                                    if ($todaySchedule && $todaySchedule->start_time && $todaySchedule->end_time) {
                                                        return $todaySchedule->start_time . ' - ' . $todaySchedule->end_time;
                                                    }
                                                    return $c->start_time ? $c->start_time . ' - ' . $c->end_time : '時間未定';
                                                });
                                            @endphp

                                            <div
                                                class="flex w-full flex-col gap-1"
                                            >
                                                @foreach ($timeGroups as $timeLabel => $classesAtTime)
                                                    <div
                                                        class="w-full rounded border border-warm-800 bg-white p-3"
                                                    >
                                                        <div
                                                            class="mb-3 text-sm font-medium text-warm-600"
                                                        >
                                                            {{ $timeLabel }}
                                                        </div>

                                                        <div
                                                            class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                                                        >
                                                            @foreach ($classesAtTime as $courseClass)
                                                                <div
                                                                    class="flex w-full flex-col gap-2"
                                                                >
                                                                    @if ($courseClass->link)
                                                                        <a
                                                                            href="{{ $courseClass->link }}"
                                                                            target="_blank"
                                                                            rel="noopener noreferrer"
                                                                            class="block w-full rounded border border-orange-200 bg-orange-50 px-4 py-3 text-left text-orange-700 transition hover:bg-orange-100"
                                                                        >
                                                                            <div
                                                                                class="text-lg font-semibold"
                                                                            >
                                                                                {{ $courseClass->code }}
                                                                            </div>
                                                                            @if ($courseClass->teacher_name)
                                                                                <div
                                                                                    class="mt-1 truncate text-sm text-warm-600"
                                                                                >
                                                                                    {{ $courseClass->teacher_name }}
                                                                                </div>
                                                                            @endif
                                                                        </a>
                                                                    @else
                                                                        <div
                                                                            class="block w-full rounded border bg-gray-50 px-4 py-3 text-left text-warm-500"
                                                                        >
                                                                            <div
                                                                                class="text-lg font-semibold"
                                                                            >
                                                                                {{ $courseClass->code }}
                                                                            </div>
                                                                            @if ($courseClass->teacher_name)
                                                                                <div
                                                                                    class="mt-1 truncate text-sm text-warm-600"
                                                                                >
                                                                                    {{ $courseClass->teacher_name }}
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endif

                                                                    @if ($courseClass->backup_classroom_url)
                                                                        <a
                                                                            href="{{ $courseClass->backup_classroom_url }}"
                                                                            target="_blank"
                                                                            rel="noopener noreferrer"
                                                                            class="inline-flex items-center justify-center gap-1 rounded border border-warm-200 bg-warm-50 px-3 py-2 text-sm font-semibold text-warm-700 transition hover:bg-warm-100"
                                                                        >
                                                                            <x-heroicon-o-squares-plus
                                                                                class="size-4"
                                                                            />
                                                                            備用教室
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </x-card>
    </div>
</x-layout>
