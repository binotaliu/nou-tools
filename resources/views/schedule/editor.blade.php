@php
    $editing = isset($schedule);
    $pageTitle = $editing ? '編輯課表' : '新增課表';
    $headingText = $editing ? '編輯您的課表' : '建立您的課表';
    $submitLabel = $editing ? '更新課表' : '建立課表';
    $submittingLabel = $editing ? '更新中...' : '建立中...';
@endphp

<x-layout title="{{ $pageTitle }} - NOU 小幫手" :noindex="true">
    <div
        x-data="scheduleEditor({ courses: {{ Js::encode($courses) }}, schedule: {{ Js::encode($schedule ?? null) }} })"
        class="mx-auto max-w-5xl"
    >
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
                {{ $headingText }}
            </h2>
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
                    @foreach ($availableTerms as $term)
                        <option
                            value="{{ $term }}"
                            @selected($term === $selectedTerm)
                        >
                            {{ \Illuminate\Support\Str::toSemesterDisplay($term) }}
                        </option>
                    @endforeach
                </x-select>
            </form>
        </div>

        @if (isset($previousSchedule) && ! isset($schedule))
            <x-alert type="warning" class="flex items-center justify-between">
                <div>
                    <div class="font-medium">
                        你曾建立過課表：
                        <span class="text-warm-900 dark:text-zinc-100">
                            {{ $previousSchedule->name ?? '（未命名）' }}
                        </span>
                        ，確定要繼續新增新課表嗎？
                    </div>
                </div>
                <div class="flex gap-2">
                    <a
                        href="{{ route('schedules.show', $previousSchedule->token) }}"
                        class="rounded bg-yellow-400 px-4 py-2 font-semibold text-yellow-900 hover:bg-yellow-500 dark:bg-yellow-600 dark:text-yellow-100 dark:hover:bg-yellow-500"
                        data-analytics-event="schedule_open_previous"
                        data-analytics-feature="schedule"
                    >
                        檢視舊課表
                    </a>
                </div>
            </x-alert>
        @endif

        {{-- Search Section --}}
        <x-card class="mb-8">
            <label
                class="mb-1 block text-xl font-semibold text-warm-900 dark:text-zinc-100"
                for="course-search"
            >
                搜尋課程
            </label>
            <div class="relative">
                <input
                    id="course-search"
                    type="text"
                    x-model="searchQuery"
                    @input="filterCourses()"
                    placeholder="輸入課程名稱..."
                    class="w-full rounded-lg border-2 border-warm-300 px-4 py-3 text-lg focus:border-orange-500 focus:outline-none dark:border-zinc-600"
                    autocomplete="off"
                    :disabled="selectedItems.length >= 10"
                />
            </div>

            {{-- Search Results Dropdown --}}
            <div
                x-show="showResults && filteredCourses.length > 0"
                class="mt-2 max-h-96 overflow-y-auto rounded-lg border border-warm-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
            >
                <template x-for="course in filteredCourses" :key="course.id">
                    <div
                        @click="selectCourse(course)"
                        :data-testid="'course-option-' + course.id"
                        class="cursor-pointer border-b border-warm-100 p-4 hover:bg-warm-50 dark:border-zinc-800 dark:hover:bg-zinc-950"
                    >
                        <div
                            class="font-semibold text-warm-900 dark:text-zinc-100"
                            x-text="course.name"
                        ></div>
                    </div>
                </template>
            </div>

            <template
                x-if="
                    showResults &&
                    filteredCourses.length === 0 &&
                    searchQuery.trim()
                "
            >
                <div
                    class="mt-2 rounded-lg border border-warm-200 bg-warm-50 p-4 text-warm-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300"
                >
                    找不到符合的課程。請試試其他關鍵字。
                </div>
            </template>
        </x-card>

        {{-- Selected Schedule Section --}}
        <x-card class="mb-8" title="您的課表">
            <template x-if="selectedItems.length === 0">
                <div
                    class="rounded-lg border-2 border-dashed border-warm-300 bg-warm-50 p-6 text-center text-warm-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-300"
                >
                    <p class="text-lg">還沒有選擇任何課程。請在上方搜尋並選擇課程。</p>
                </div>
            </template>

            <div class="space-y-4">
                <template x-for="(item, index) in selectedItems" :key="index">
                    <div
                        :data-testid="'selected-item-' + item.course.id"
                        class="rounded-lg border-2 border-warm-300 bg-warm-50 p-4 dark:border-zinc-600 dark:bg-zinc-950"
                    >
                        <div class="mb-3 flex items-start justify-between">
                            <div>
                                <div
                                    class="text-lg font-bold text-warm-900 dark:text-zinc-100"
                                    x-text="item.course.name"
                                ></div>
                            </div>
                            <x-button
                                variant="danger"
                                size="sm"
                                @click="removeItem(index)"
                            >
                                移除
                            </x-button>
                        </div>

                        {{-- Class Selection --}}
                        <div class="mt-3">
                            <template x-if="!item.course.has_classes">
                                <div
                                    data-testid="pending-class-note"
                                    class="rounded-lg border-2 border-dashed border-warm-300 bg-warm-100 p-3 text-sm text-warm-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"
                                >
                                    尚未開課，選課後將自動列入課表，開課後請記得回來選擇班級。
                                </div>
                            </template>

                            <template
                                x-if="
                                    item.course.has_classes &&
                                    getClassTypes(item.course).length > 1
                                "
                            >
                                <div>
                                    <fieldset class="mb-4">
                                        <legend
                                            class="mb-2 text-sm font-semibold text-warm-800 dark:text-zinc-200"
                                        >
                                            選擇班級：
                                        </legend>

                                        <template
                                            x-for="
                                                type in
                                                getClassTypes(item.course)
                                            "
                                        >
                                            <fieldset :key="type" class="mb-4">
                                                <legend
                                                    class="mb-2 text-sm font-semibold text-warm-700 dark:text-zinc-300"
                                                    x-text="getTypeLabel(type)"
                                                ></legend>
                                                <div
                                                    class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
                                                >
                                                    <template
                                                        x-for="
                                                            courseClass in
                                                            getClassesByType(
                                                                item.course,
                                                                type
                                                            )
                                                        "
                                                        :key="courseClass.id"
                                                    >
                                                        <label
                                                            :data-testid="courseClass.is_tentative
                                                                ? 'tentative-session-' +
                                                                  courseClass.type
                                                                : null"
                                                            class="flex cursor-pointer items-start rounded-lg border-2 bg-white p-3 transition hover:border-orange-300 dark:bg-zinc-900"
                                                            :class="item.selectedClassId ===
                                                            courseClass.id
                                                                ? 'border-orange-500 bg-orange-50'
                                                                : 'border-warm-200 dark:border-zinc-700'"
                                                        >
                                                            <input
                                                                type="radio"
                                                                :name="'class_' +
                                                                index"
                                                                :value="courseClass.id"
                                                                x-model.number="
                                                                    item.selectedClassId
                                                                "
                                                                class="mt-1 mr-3 h-5 w-5 cursor-pointer"
                                                            />
                                                            <div
                                                                class="min-w-0 flex-1"
                                                            >
                                                                <div
                                                                    class="font-semibold text-warm-900 dark:text-zinc-100"
                                                                    x-text="
                                                                        courseClass.is_tentative
                                                                            ? courseClass.type_label
                                                                            : courseClass.code
                                                                    "
                                                                ></div>
                                                                <div
                                                                    class="text-xs font-semibold text-amber-700 dark:text-amber-400"
                                                                    x-show="
                                                                        courseClass.is_tentative
                                                                    "
                                                                >
                                                                    尚未正式分班
                                                                </div>
                                                                <div
                                                                    class="text-sm text-warm-600 dark:text-zinc-400"
                                                                    x-show="
                                                                        courseClass.start_time
                                                                    "
                                                                >
                                                                    <span
                                                                        x-text="
                                                                            courseClass.start_time +
                                                                            ' - ' +
                                                                            courseClass.end_time
                                                                        "
                                                                    ></span>
                                                                </div>
                                                                <div
                                                                    class="truncate text-sm text-warm-600 dark:text-zinc-400"
                                                                    x-show="
                                                                        courseClass.teacher_name
                                                                    "
                                                                    x-text="
                                                                        courseClass.teacher_name
                                                                    "
                                                                ></div>
                                                            </div>
                                                        </label>
                                                    </template>
                                                </div>
                                            </fieldset>
                                        </template>
                                    </fieldset>
                                </div>
                            </template>

                            <template
                                x-if="
                                    item.course.has_classes &&
                                    getClassTypes(item.course).length === 1
                                "
                            >
                                <div>
                                    <fieldset>
                                        <legend
                                            class="mb-2 text-sm font-semibold text-warm-800 dark:text-zinc-200"
                                        >
                                            班級：
                                        </legend>
                                        <div
                                            class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
                                        >
                                            <template
                                                x-for="
                                                    courseClass in
                                                    item.course.classes
                                                "
                                                :key="courseClass.id"
                                            >
                                                <label
                                                    :data-testid="courseClass.is_tentative
                                                        ? 'tentative-session-' +
                                                          courseClass.type
                                                        : null"
                                                    class="flex cursor-pointer items-start rounded-lg border-2 bg-white p-3 transition hover:border-orange-300 dark:bg-zinc-900"
                                                    :class="item.selectedClassId ===
                                                    courseClass.id
                                                        ? 'border-orange-500 bg-orange-50'
                                                        : 'border-warm-200 dark:border-zinc-700'"
                                                >
                                                    <input
                                                        type="radio"
                                                        :name="'class_' + index"
                                                        :value="courseClass.id"
                                                        x-model.number="
                                                            item.selectedClassId
                                                        "
                                                        class="mt-1 mr-3 h-5 w-5 cursor-pointer"
                                                    />
                                                    <div class="min-w-0 flex-1">
                                                        <div
                                                            class="font-semibold text-warm-900 dark:text-zinc-100"
                                                            x-text="
                                                                courseClass.is_tentative
                                                                    ? courseClass.type_label
                                                                    : courseClass.code
                                                            "
                                                        ></div>
                                                        <div
                                                            class="text-xs font-semibold text-amber-700 dark:text-amber-400"
                                                            x-show="
                                                                courseClass.is_tentative
                                                            "
                                                        >
                                                            尚未正式分班
                                                        </div>
                                                        <div
                                                            class="text-sm text-warm-600 dark:text-zinc-400"
                                                            x-show="
                                                                courseClass.start_time
                                                            "
                                                        >
                                                            <span
                                                                x-text="
                                                                    courseClass.start_time +
                                                                    ' - ' +
                                                                    courseClass.end_time
                                                                "
                                                            ></span>
                                                        </div>
                                                        <div
                                                            class="truncate text-sm text-warm-600 dark:text-zinc-400"
                                                            x-show="
                                                                courseClass.teacher_name
                                                            "
                                                            x-text="
                                                                courseClass.teacher_name
                                                            "
                                                        ></div>
                                                    </div>
                                                </label>
                                            </template>
                                        </div>
                                    </fieldset>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </x-card>

        {{-- Submit Section --}}
        <form
            action="{{ isset($schedule) ? route('schedules.update', $schedule) : route('schedules.store') }}"
            method="POST"
            x-ref="form"
            @submit.prevent="submitForm"
            class="rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
            @csrf
            @if (isset($schedule))
                @method('PUT')
            @endif
            <input type="hidden" name="term" value="{{ $selectedTerm }}" />

            <div class="mb-4">
                <label
                    class="mb-1 block text-xl font-semibold text-warm-900 dark:text-zinc-100"
                    for="schedule-name"
                >
                    課表名稱（可選）
                </label>
                <input
                    id="schedule-name"
                    type="text"
                    name="name"
                    x-model="scheduleName"
                    placeholder="例如：浣熊的課表"
                    class="w-full rounded-lg border-2 border-warm-300 px-4 py-3 focus:border-orange-500 focus:outline-none dark:border-zinc-600"
                />
            </div>

            {{-- hidden fields for selected items --}}
            <template
                x-for="(item, index) in selectedItems"
                :key="item.course.id"
            >
                <span>
                    <input
                        type="hidden"
                        :name="'items[' + index + '][course_id]'"
                        :value="item.course.id"
                    />
                    <template x-if="item.selectedClassId">
                        <input
                            type="hidden"
                            :name="'items[' + index + '][class_id]'"
                            :value="item.selectedClassId"
                        />
                    </template>
                </span>
            </template>

            <div class="flex gap-4">
                <x-button
                    type="submit"
                    variant="primary"
                    size="lg"
                    full-width
                    data-testid="schedule-submit"
                    ::disabled="selectedItems.length === 0 || submitting"
                >
                    <span x-show="!submitting">{{ $submitLabel }}</span>
                    <span x-show="submitting">{{ $submittingLabel }}</span>
                </x-button>
                <x-link-button
                    :href="isset($schedule) ? route('schedules.show', $schedule) : route('schedules.create')"
                    variant="secondary"
                    size="lg"
                    full-width
                >
                    取消
                </x-link-button>
            </div>
        </form>
    </div>
</x-layout>
