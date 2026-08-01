@php
    $seoDescription = '國立空中大學 '.\Illuminate\Support\Str::toSemesterDisplay($page->selectedTerm).'開課表，查詢各學系課程的學分數與考試時間。';
@endphp

<x-layout title="本學期開課表 - NOU 小幫手" :description="$seoDescription">
    @php
        $courseFrontEndData = $page->groups->toCollection()
            ->flatMap(fn ($group) => $group->courses->toCollection()->map(fn ($course) => [
                'id' => $course->id,
                'name' => $course->name,
                'department' => $course->department,
                'credits' => $course->credits,
                'section' => 'general',
                'examLabel' => $group->label,
                'examWeekdayOrder' => $group->weekdayOrder,
                'examTimeStart' => $group->examTimeStart,
                'url' => route('course.show', $course->id),
            ]))
            ->concat(
                $page->microCreditOrRemoteCourses->toCollection()->map(fn ($course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'department' => $course->department,
                    'credits' => $course->credits,
                    'section' => 'micro',
                    'examLabel' => null,
                    'examWeekdayOrder' => null,
                    'examTimeStart' => null,
                    'url' => route('course.show', $course->id),
                ])
            )
            ->values();

        $departmentOptions = $courseFrontEndData->pluck('department')->filter()->unique()->sort()->values();
        $creditOptions = $courseFrontEndData->pluck('credits')->filter(fn ($credits) => $credits !== null)->unique()->sort()->values();
    @endphp

    <div
        class="mx-auto max-w-5xl"
        x-data="courseSchedule({
                    courses: {{ Js::encode($courseFrontEndData) }},
                })"
        x-cloak
    >
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2
                    class="mb-2 text-3xl font-bold text-warm-900 dark:text-zinc-100"
                >
                    本學期開課表
                </h2>

                <div class="text-sm text-warm-600 dark:text-zinc-400">
                    {{ \Illuminate\Support\Str::toSemesterDisplay($page->selectedTerm) }}
                </div>
            </div>

            <form
                method="GET"
                action="{{ url()->current() }}"
                class="w-full sm:w-auto sm:min-w-40"
            >
                <label for="term" class="sr-only">選擇學期</label>
                <x-select
                    id="term"
                    name="term"
                    @change="$event.target.form.submit()"
                    aria-label="選擇學期"
                >
                    @foreach ($page->availableTerms as $term)
                        <option
                            value="{{ $term }}"
                            @selected($term === $page->selectedTerm)
                        >
                            {{ \Illuminate\Support\Str::toSemesterDisplay($term) }}
                        </option>
                    @endforeach
                </x-select>
            </form>
        </div>

        {{-- Filters --}}
        <x-card class="mb-6">
            <form
                @submit.prevent
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div>
                    <label
                        for="search"
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                    >
                        搜尋
                    </label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        x-model.debounce.500ms="search"
                        placeholder="課程名稱..."
                        class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                    />
                </div>
                <div>
                    <label
                        for="groupBy"
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                    >
                        分組方式
                    </label>
                    <x-select
                        id="groupBy"
                        x-model="groupBy"
                        aria-label="分組方式"
                        data-testid="group-by-select"
                    >
                        <option value="exam">考試時間</option>
                        <option value="department">學系</option>
                        <option value="credits">學分數</option>
                    </x-select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                    >
                        學系
                    </label>
                    <div
                        class="relative"
                        x-data="{ open: false }"
                        @click.outside="open = false"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-center justify-between rounded-lg border border-warm-200 px-3 py-2 text-left text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                        >
                            <span
                                x-text="
                                    department.length
                                        ? '已選 ' + department.length + ' 項'
                                        : '全部學系'
                                "
                            ></span>
                            <x-heroicon-o-chevron-down
                                class="size-4 text-gray-400"
                            />
                        </button>
                        <div
                            x-show="open"
                            x-transition
                            x-cloak
                            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-warm-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                        >
                            @foreach ($departmentOptions as $department)
                                <label
                                    class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-warm-50 dark:hover:bg-zinc-700"
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $department }}"
                                        x-model="department"
                                        class="rounded border-warm-300 dark:border-zinc-600"
                                    />
                                    {{ $department }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                    >
                        學分
                    </label>
                    <div
                        class="relative"
                        x-data="{ open: false }"
                        @click.outside="open = false"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-center justify-between rounded-lg border border-warm-200 px-3 py-2 text-left text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                        >
                            <span
                                x-text="
                                    credits.length
                                        ? '已選 ' + credits.length + ' 項'
                                        : '全部學分'
                                "
                            ></span>
                            <x-heroicon-o-chevron-down
                                class="size-4 text-gray-400"
                            />
                        </button>
                        <div
                            x-show="open"
                            x-transition
                            x-cloak
                            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-warm-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                        >
                            @foreach ($creditOptions as $creditOption)
                                <label
                                    class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-warm-50 dark:hover:bg-zinc-700"
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $creditOption }}"
                                        x-model="credits"
                                        class="rounded border-warm-300 dark:border-zinc-600"
                                    />
                                    {{ $creditOption }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex items-end">
                    <x-link-button
                        href="#"
                        variant="secondary"
                        x-show="hasFilters"
                        @click.prevent="clearFilters()"
                    >
                        清除條件
                    </x-link-button>
                </div>
            </form>
        </x-card>

        <template x-for="section in sections" :key="section.key">
            <x-card
                class="mb-6"
                x-bind:data-testid="'schedule-section-' + section.key"
            >
                <h2
                    class="mb-4 text-xl font-semibold text-warm-900 dark:text-zinc-100"
                    x-text="section.title"
                ></h2>

                <template x-if="section.groups.length === 0">
                    <p
                        class="text-sm text-warm-600 dark:text-zinc-400"
                        x-text="section.emptyMessage"
                    ></p>
                </template>

                <div class="space-y-6" x-show="section.groups.length > 0">
                    <template x-for="group in section.groups" :key="group.key">
                        <div>
                            <div
                                x-show="group.label"
                                class="mb-3 font-semibold text-warm-900 dark:text-zinc-100"
                                x-text="group.label"
                            ></div>

                            {{-- 桌面版表格 --}}
                            <div
                                class="hidden overflow-x-auto md:block"
                                data-testid="schedule-desktop-table"
                            >
                                <table
                                    class="w-full border-collapse overflow-hidden rounded text-left text-warm-700 dark:text-zinc-300"
                                >
                                    <caption class="sr-only">
                                        課程列表
                                    </caption>
                                    <thead
                                        class="border-b-2 border-warm-300 bg-warm-100 dark:border-zinc-600 dark:bg-zinc-900"
                                    >
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                                            >
                                                課程名稱
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                                                :class="fieldMeta[columns[0]]
                                                    .thClass"
                                                x-text="
                                                    fieldMeta[columns[0]].title
                                                "
                                            ></th>
                                            <th
                                                scope="col"
                                                class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                                                :class="fieldMeta[columns[1]]
                                                    .thClass"
                                                x-text="
                                                    fieldMeta[columns[1]].title
                                                "
                                            ></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <template
                                            x-for="course in group.courses"
                                            :key="course.id"
                                        >
                                            <tr
                                                class="border-b border-warm-200 hover:bg-warm-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
                                            >
                                                <th
                                                    scope="row"
                                                    class="px-4 py-3 font-normal text-warm-800 dark:text-zinc-200"
                                                >
                                                    <a
                                                        :href="course.url"
                                                        class="inline-flex items-center gap-2 text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                                                        x-text="course.name"
                                                    ></a>
                                                </th>
                                                <td
                                                    class="px-4 py-3 text-warm-800 dark:text-zinc-200"
                                                    :class="fieldMeta[
                                                        columns[0]
                                                    ].tdClass"
                                                    x-text="
                                                        columnValue(
                                                            course,
                                                            columns[0]
                                                        )
                                                    "
                                                ></td>
                                                <td
                                                    class="px-4 py-3 text-warm-800 dark:text-zinc-200"
                                                    :class="fieldMeta[
                                                        columns[1]
                                                    ].tdClass"
                                                    x-text="
                                                        columnValue(
                                                            course,
                                                            columns[1]
                                                        )
                                                    "
                                                ></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- 手機版卡片列表 --}}
                            <div
                                class="space-y-3 md:hidden"
                                data-testid="schedule-mobile-cards"
                            >
                                <template
                                    x-for="course in group.courses"
                                    :key="course.id"
                                >
                                    <div
                                        class="rounded-lg border border-warm-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        <a
                                            :href="course.url"
                                            class="text-base font-semibold text-orange-600 underline underline-offset-4 hover:text-orange-700 hover:no-underline"
                                            x-text="course.name"
                                        ></a>
                                        <div
                                            class="mt-1 flex items-center gap-2 text-sm text-warm-600 dark:text-zinc-400"
                                        >
                                            <span
                                                x-text="
                                                    mobileColumnValue(
                                                        course,
                                                        columns[0]
                                                    )
                                                "
                                            ></span>
                                            <span>·</span>
                                            <span
                                                class="tabular-nums"
                                                x-text="
                                                    mobileColumnValue(
                                                        course,
                                                        columns[1]
                                                    )
                                                "
                                            ></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </x-card>
        </template>
    </div>
</x-layout>
