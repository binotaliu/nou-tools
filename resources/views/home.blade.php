@push('head')
    <x-json-ld
        :data="[
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'NOU 小幫手',
            'description' => '給 NOU 同學的非官方小工具：管理個人課表與學習進度。NOU 小幫手是一款由同學自行開發，專為國立空中大學同學設計的非官方小工具。通過 NOU 小幫手，同學可輕鬆管理自己的課表、學習進度，掌握視訊面授及考試時間，並隨時取得最新的學校公告。另外，NOU 小幫手也提供了優惠店家清單，讓同學在校園生活中享受更多便利與優惠。NOU 小幫手致力於為同學提供一個簡單、方便、實用的學習工具，讓同學能夠更好地規劃自己的學習生活。',
            'url' => url('/'),
        ]"
    />
@endpush

<x-layout>
    <div class="space-y-8">
        <div
            x-data
            x-show="$store.network.offline"
            x-cloak
            class="mb-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 print:hidden"
            role="status"
            aria-live="polite"
        >
            <x-heroicon-o-signal-slash class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-semibold">目前處於離線狀態</p>
                <p class="mt-1">這是先前載入過的快取內容，可能不是最新資料。部分需要連線的功能已停用，例如今日視訊面授的日期切換。</p>
            </div>
        </div>

        <x-greeting />

        <div
            class="flex flex-col gap-4 md:flex-row md:items-stretch md:justify-between"
        >
            <x-card title="功能選單">
                @if (isset($viewModel->previousSchedule))
                    <x-link-button
                        :href="route('schedules.show', $viewModel->previousSchedule->token)"
                        variant="warm-dark"
                        full-width
                        data-analytics-event="schedule_open_previous"
                        data-analytics-feature="schedule"
                    >
                        <x-heroicon-o-table-cells class="size-4" />

                        <span class="max-w-xs truncate">
                            {{ $viewModel->previousSchedule->name ?? '（未命名）' }}
                        </span>
                    </x-link-button>
                @else
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
                @endif

                <x-link-button
                    :href="route('announcements.index')"
                    variant="secondary"
                    full-width
                    class="mt-3"
                >
                    <x-heroicon-o-megaphone class="size-4" />

                    學校公告
                </x-link-button>

                <x-link-button
                    :href="route('directory.index')"
                    variant="secondary"
                    full-width
                    class="mt-3"
                    data-offline-allow
                >
                    <x-heroicon-o-map class="size-4" />

                    連結 / 學習指導中心目錄
                </x-link-button>

                @if (isset($viewModel->previousSchedule))
                    <div class="mt-3 w-full text-center text-sm">
                        <a
                            href="{{ route('schedules.create') }}"
                            class="text-warm-600 underline hover:text-warm-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                            data-analytics-event="schedule_create_start"
                            data-analytics-feature="schedule"
                        >
                            建立新課表
                        </a>
                    </div>
                @endif
            </x-card>

            <x-common-links />
        </div>

        {{-- School Calendar --}}
        <x-school-calendar />

        {{-- 今日面授 --}}
        <x-card
            x-data="nouDatePicker({ date: '{{ $viewModel->selectedDate }}' })"
            title="今日視訊面授"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <label
                        for="video-course-date"
                        class="text-sm text-warm-500 dark:text-zinc-400"
                    >
                        選擇日期
                    </label>
                    <input
                        type="date"
                        id="video-course-date"
                        class="rounded border px-3 py-1 text-sm"
                        x-model="date"
                        @change="navigate()"
                        :value="date"
                        data-offline-disable
                    />
                </div>
            </div>

            @php
                $courses = $viewModel->courses->toCollection();
            @endphp

            <div class="mt-4 space-y-6">
                @if ($courses->isEmpty())
                    <div
                        class="flex min-h-64 items-center justify-center gap-x-2 text-2xl text-warm-500 dark:text-zinc-400"
                    >
                        <x-heroicon-o-face-smile class="size-8" />
                        今日無面授課程
                    </div>
                @else
                    @foreach ($courses as $course)
                        <div>
                            <h4
                                class="mb-3 font-semibold text-warm-800 dark:text-zinc-200"
                            >
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
                                    $grouped = $course->classes->toCollection()->groupBy(fn ($class) => in_array($class->type->value, array_keys($typeLabels)) ? $class->type : 'other');
                                @endphp

                                @foreach ($typeLabels as $typeKey => $label)
                                    @if (isset($grouped[$typeKey]) && $grouped[$typeKey]->isNotEmpty())
                                        <div
                                            class="flex flex-col items-stretch gap-2"
                                        >
                                            <div
                                                class="text-sm font-semibold text-warm-700 dark:text-zinc-300"
                                            >
                                                {{ $label }}
                                            </div>

                                            @php
                                                // group classes by start/end time so we show the time once per time slot
                                                // If there's a schedule override for today, use that instead
                                                $timeGroups = $grouped[$typeKey]->groupBy(function ($c) {
                                                    $todaySession = $c->sessions->first();
                                                    if ($todaySession && $todaySession->startTime && $todaySession->endTime) {
                                                        return $todaySession->startTime . ' - ' . $todaySession->endTime;
                                                    }
                                                    return $c->startTime ? $c->startTime . ' - ' . $c->endTime : '時間未定';
                                                });
                                            @endphp

                                            <div
                                                class="flex w-full flex-col gap-1"
                                            >
                                                @foreach ($timeGroups as $timeLabel => $classesAtTime)
                                                    <div
                                                        class="w-full rounded border border-warm-800 bg-white p-3 dark:border-zinc-600 dark:bg-zinc-900"
                                                    >
                                                        <div
                                                            class="mb-3 text-sm font-medium text-warm-600 dark:text-zinc-400"
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
                                                                            class="block w-full rounded border border-orange-200 bg-orange-50 px-4 py-3 text-left text-orange-700 transition hover:bg-orange-100 dark:border-orange-800/60 dark:bg-orange-950/60 dark:text-orange-300 dark:hover:bg-orange-950"
                                                                        >
                                                                            <div
                                                                                class="text-lg font-semibold"
                                                                            >
                                                                                {{ $courseClass->code }}
                                                                            </div>
                                                                            @if ($courseClass->teacherName)
                                                                                <div
                                                                                    class="mt-1 truncate text-sm text-warm-600 dark:text-zinc-400"
                                                                                >
                                                                                    {{ $courseClass->teacherName }}
                                                                                </div>
                                                                            @endif
                                                                        </a>
                                                                    @else
                                                                        <div
                                                                            class="block w-full rounded border bg-gray-50 px-4 py-3 text-left text-warm-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400"
                                                                        >
                                                                            <div
                                                                                class="text-lg font-semibold"
                                                                            >
                                                                                {{ $courseClass->code }}
                                                                            </div>
                                                                            @if ($courseClass->teacherName)
                                                                                <div
                                                                                    class="mt-1 truncate text-sm text-warm-600 dark:text-zinc-400"
                                                                                >
                                                                                    {{ $courseClass->teacherName }}
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endif

                                                                    @if ($courseClass->backupClassroomUrl)
                                                                        <a
                                                                            href="{{ $courseClass->backupClassroomUrl }}"
                                                                            target="_blank"
                                                                            rel="noopener noreferrer"
                                                                            class="inline-flex items-center justify-center gap-1 rounded border border-warm-200 bg-warm-50 px-3 py-2 text-sm font-semibold text-warm-700 transition hover:bg-warm-100 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-900"
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
