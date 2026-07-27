<x-layout title="本學期開課表 - NOU 小幫手">
    @php
        $courseFrontEndData = $page->groups->toCollection()
            ->flatMap(fn ($group) => $group->courses->toCollection()->map(fn ($course) => [
                'id' => $course->id,
                'name' => $course->name,
                'department' => $course->department,
                'credits' => $course->credits,
                'section' => 'general',
            ]))
            ->concat(
                $page->microCreditOrRemoteCourses->toCollection()->map(fn ($course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'department' => $course->department,
                    'credits' => $course->credits,
                    'section' => 'micro',
                ])
            )
            ->values();

        $departmentOptions = $courseFrontEndData->pluck('department')->filter()->unique()->sort()->values();
        $creditOptions = $courseFrontEndData->pluck('credits')->filter(fn ($credits) => $credits !== null)->unique()->sort()->values();
    @endphp

    <div
        class="mx-auto max-w-5xl"
        x-data="courseSchedule({
                    courses: {{ Js::from($courseFrontEndData) }},
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
                    onchange="this.form.submit()"
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
                                        ? `已選 ${department.length} 項`
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
                                        ? `已選 ${credits.length} 項`
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

        <x-card class="mb-6" title="一般課程">
            @if ($page->groups->count() === 0)
                <p class="text-sm text-warm-600 dark:text-zinc-400">目前查無考試時間資料。</p>
            @else
                <div class="space-y-6">
                    @foreach ($page->groups as $group)
                        @php
                            $groupCourseIds = $group->courses->toCollection()->pluck('id')->implode(',');
                        @endphp
                        <div
                            x-show="groupHasVisibleCourses('{{ $groupCourseIds }}')"
                        >
                            <div
                                class="mb-3 font-semibold text-warm-900 dark:text-zinc-100"
                            >
                                {{ $group->label }}
                            </div>

                            {{-- 桌面版表格 --}}
                            <div class="hidden overflow-x-auto md:block">
                                <x-table :caption="$group->label">
                                    <x-table-head>
                                        <x-table-row>
                                            <x-table-head-column
                                                class="w-16 text-center"
                                            >
                                                學分
                                            </x-table-head-column>
                                            <x-table-head-column class="w-42">
                                                學系
                                            </x-table-head-column>
                                            <x-table-head-column>
                                                課程名稱
                                            </x-table-head-column>
                                        </x-table-row>
                                    </x-table-head>

                                    <x-table-body>
                                        @foreach ($group->courses as $course)
                                            <x-table-row
                                                x-show="isCourseVisible({{ $course->id }})"
                                            >
                                                <x-table-column
                                                    class="text-center tabular-nums"
                                                >
                                                    {{ $course->credits ?? '—' }}
                                                </x-table-column>
                                                <x-table-column>
                                                    {{ $course->department ?? '—' }}
                                                </x-table-column>
                                                <x-table-head-column
                                                    scope="row"
                                                >
                                                    <x-link-button
                                                        :href="route('course.show', $course->id)"
                                                        variant="link"
                                                    >
                                                        {{ $course->name }}
                                                    </x-link-button>
                                                </x-table-head-column>
                                            </x-table-row>
                                        @endforeach
                                    </x-table-body>
                                </x-table>
                            </div>

                            {{-- 手機版卡片列表 --}}
                            <div class="space-y-3 md:hidden">
                                @foreach ($group->courses as $course)
                                    <div
                                        x-show="isCourseVisible({{ $course->id }})"
                                        class="rounded-lg border border-warm-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        <x-link-button
                                            :href="route('course.show', $course->id)"
                                            variant="link"
                                            class="text-base font-semibold"
                                        >
                                            {{ $course->name }}
                                        </x-link-button>
                                        <div
                                            class="mt-1 flex items-center gap-2 text-sm text-warm-600 dark:text-zinc-400"
                                        >
                                            <span
                                                >{{ $course->department ?? '—' }}</span
                                            >
                                            <span>·</span>
                                            <span class="tabular-nums"
                                                >{{ $course->credits ?? '—' }} 學分</span
                                            >
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <p
                    class="text-sm text-warm-600 dark:text-zinc-400"
                    x-show="generalVisibleCount === 0"
                >目前沒有符合篩選條件的課程。</p>
            @endif
        </x-card>

        <x-card class="mb-6" title="微學分與全遠距">
            @if ($page->microCreditOrRemoteCourses->count() === 0)
                <p class="text-sm text-warm-600 dark:text-zinc-400">目前查無微學分或全遠距課程。</p>
            @else
                {{-- 桌面版表格 --}}
                <div class="hidden overflow-x-auto md:block">
                    <x-table caption="微學分與全遠距">
                        <x-table-head>
                            <x-table-row>
                                <x-table-head-column class="w-16 text-center">
                                    學分
                                </x-table-head-column>
                                <x-table-head-column class="w-42">
                                    學系
                                </x-table-head-column>
                                <x-table-head-column>
                                    課程名稱
                                </x-table-head-column>
                            </x-table-row>
                        </x-table-head>

                        <x-table-body>
                            @foreach ($page->microCreditOrRemoteCourses as $course)
                                <x-table-row
                                    x-show="isCourseVisible({{ $course->id }})"
                                >
                                    <x-table-column
                                        class="text-center tabular-nums"
                                    >
                                        {{ $course->credits ?? '—' }}
                                    </x-table-column>
                                    <x-table-column>
                                        {{ $course->department ?? '—' }}
                                    </x-table-column>
                                    <x-table-head-column scope="row">
                                        <x-link-button
                                            :href="route('course.show', $course->id)"
                                            variant="link"
                                        >
                                            {{ $course->name }}
                                        </x-link-button>
                                    </x-table-head-column>
                                </x-table-row>
                            @endforeach
                        </x-table-body>
                    </x-table>
                </div>

                {{-- 手機版卡片列表 --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($page->microCreditOrRemoteCourses as $course)
                        <div
                            x-show="isCourseVisible({{ $course->id }})"
                            class="rounded-lg border border-warm-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <x-link-button
                                :href="route('course.show', $course->id)"
                                variant="link"
                                class="text-base font-semibold"
                            >
                                {{ $course->name }}
                            </x-link-button>
                            <div
                                class="mt-1 flex items-center gap-2 text-sm text-warm-600 dark:text-zinc-400"
                            >
                                <span>{{ $course->department ?? '—' }}</span>
                                <span>·</span>
                                <span class="tabular-nums"
                                    >{{ $course->credits ?? '—' }} 學分</span
                                >
                            </div>
                        </div>
                    @endforeach
                </div>

                <p
                    class="text-sm text-warm-600 dark:text-zinc-400"
                    x-show="microVisibleCount === 0"
                >目前沒有符合篩選條件的課程。</p>
            @endif
        </x-card>
    </div>

    <script>
        function courseSchedule(config) {
            return {
                courses: config.courses,
                search: '',
                department: [],
                credits: [],

                get normalizedSearch() {
                    return this.search.trim().toLowerCase()
                },

                get filteredCourseIds() {
                    return new Set(
                        this.courses
                            .filter(course => {
                                const matchesSearch =
                                    this.normalizedSearch === '' ||
                                    course.name
                                        .toLowerCase()
                                        .includes(this.normalizedSearch)
                                const matchesDepartment =
                                    this.department.length === 0 ||
                                    this.department.includes(course.department)
                                const matchesCredits =
                                    this.credits.length === 0 ||
                                    this.credits.includes(
                                        String(course.credits)
                                    )

                                return (
                                    matchesSearch &&
                                    matchesDepartment &&
                                    matchesCredits
                                )
                            })
                            .map(course => course.id)
                    )
                },

                get generalVisibleCount() {
                    return this.courses.filter(
                        course =>
                            course.section === 'general' &&
                            this.filteredCourseIds.has(course.id)
                    ).length
                },

                get microVisibleCount() {
                    return this.courses.filter(
                        course =>
                            course.section === 'micro' &&
                            this.filteredCourseIds.has(course.id)
                    ).length
                },

                get hasFilters() {
                    return (
                        this.search ||
                        this.department.length ||
                        this.credits.length
                    )
                },

                isCourseVisible(courseId) {
                    return this.filteredCourseIds.has(Number(courseId))
                },

                groupHasVisibleCourses(courseIds) {
                    return courseIds
                        .split(',')
                        .filter(Boolean)
                        .some(id => this.filteredCourseIds.has(Number(id)))
                },

                clearFilters() {
                    this.search = ''
                    this.department = []
                    this.credits = []
                },
            }
        }
    </script>
</x-layout>
