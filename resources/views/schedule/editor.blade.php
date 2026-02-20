@extends('layouts.app')

@section('title', '編輯課表 - NOU 小幫手')

@section('content')
    <div x-data="scheduleEditor()" class="mx-auto max-w-5xl">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-3xl font-bold text-warm-900">編輯您的課表</h2>
            <div class="text-lg font-semibold text-orange-600">
                {{ \Illuminate\Support\Str::toSemesterDisplay(config('app.current_semester')) }}
            </div>
        </div>

        @if (isset($previousSchedule) && ! isset($schedule))
            <x-alert type="warning" class="flex items-center justify-between">
                <div>
                    <div class="font-medium">
                        你曾建立過課表：
                        <span class="text-warm-900">
                            {{ $previousSchedule->name ?? '（未命名）' }}
                        </span>
                        ，確定要繼續新增新課表嗎？
                    </div>
                </div>
                <div class="flex gap-2">
                    <a
                        href="{{ route('schedules.show', $previousSchedule->token) }}"
                        class="rounded bg-yellow-400 px-4 py-2 font-semibold text-yellow-900 hover:bg-yellow-500"
                    >
                        檢視舊課表
                    </a>
                </div>
            </x-alert>
        @endif

        {{-- Search Section --}}
        <x-card class="mb-8">
            <label class="mb-3 block text-lg font-semibold text-warm-900">
                搜尋課程
            </label>
            <div class="relative">
                <input
                    type="text"
                    x-model="searchQuery"
                    @input="filterCourses()"
                    placeholder="輸入課程名稱..."
                    class="w-full rounded-lg border-2 border-warm-300 px-4 py-3 text-lg focus:border-orange-500 focus:outline-none"
                    autocomplete="off"
                />
            </div>

            {{-- Search Results Dropdown --}}
            <div
                x-show="showResults && filteredCourses.length > 0"
                class="mt-2 max-h-96 overflow-y-auto rounded-lg border border-warm-200 bg-white shadow-lg"
            >
                <template x-for="course in filteredCourses" :key="course.id">
                    <div
                        @click="selectCourse(course)"
                        class="cursor-pointer border-b border-warm-100 p-4 hover:bg-warm-50"
                    >
                        <div
                            class="font-semibold text-warm-900"
                            x-text="course.name"
                        ></div>
                    </div>
                </template>
            </div>

            <template
                x-if="showResults && filteredCourses.length === 0 && searchQuery.trim()"
            >
                <div
                    class="mt-2 rounded-lg border border-warm-200 bg-warm-50 p-4 text-warm-700"
                >
                    找不到符合的課程。請試試其他關鍵字。
                </div>
            </template>
        </x-card>

        {{-- Selected Schedule Section --}}
        <x-card class="mb-8">
            <h3 class="mb-4 text-2xl font-bold text-warm-900">您的課表</h3>

            <template x-if="selectedItems.length === 0">
                <div
                    class="rounded-lg border-2 border-dashed border-warm-300 bg-warm-50 p-6 text-center text-warm-700"
                >
                    <p class="text-lg">
                        還沒有選擇任何課程。請在上方搜尋並選擇課程。
                    </p>
                </div>
            </template>

            <div class="space-y-4">
                <template
                    x-for="(item, index) in selectedItems"
                    :key="index"
                >
                    <div
                        class="rounded-lg border-2 border-warm-300 bg-warm-50 p-4"
                    >
                        <div class="mb-3 flex items-start justify-between">
                            <div>
                                <div
                                    class="text-lg font-bold text-warm-900"
                                    x-text="item.course.name"
                                ></div>
                            </div>
                            <button
                                @click="removeItem(index)"
                                class="rounded bg-red-100 px-3 py-1 text-sm font-semibold text-red-700 hover:bg-red-200"
                            >
                                移除
                            </button>
                        </div>

                        {{-- Class Selection --}}
                        <div class="mt-3">
                            <template
                                x-if="getClassTypes(item.course).length > 1"
                            >
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold text-warm-800"
                                    >
                                        選擇班級：
                                    </label>

                                    <template
                                        x-for="type in getClassTypes(item.course)"
                                    >
                                        <div :key="type" class="mb-4">
                                            <div
                                                class="mb-2 text-sm font-semibold text-warm-700"
                                                x-text="getTypeLabel(type)"
                                            ></div>
                                            <div
                                                class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
                                            >
                                                <template
                                                    x-for="courseClass in getClassesByType(item.course, type)"
                                                    :key="courseClass.id"
                                                >
                                                    <label
                                                        class="flex cursor-pointer items-start rounded-lg border-2 bg-white p-3 transition hover:border-orange-300"
                                                        :class="item.selectedClassId === courseClass.id ? 'border-orange-500 bg-orange-50' : 'border-warm-200'"
                                                    >
                                                        <input
                                                            type="radio"
                                                            :name="`class_${index}`"
                                                            :value="courseClass.id"
                                                            x-model.number="item.selectedClassId"
                                                            class="mt-1 mr-3 h-5 w-5 cursor-pointer"
                                                        />
                                                        <div
                                                            class="min-w-0 flex-1"
                                                        >
                                                            <div
                                                                class="font-semibold text-warm-900"
                                                                x-text="courseClass.code"
                                                            ></div>
                                                            <div
                                                                class="text-sm text-warm-600"
                                                                x-show="courseClass.start_time"
                                                            >
                                                                <span
                                                                    x-text="`${courseClass.start_time} - ${courseClass.end_time}`"
                                                                ></span>
                                                            </div>
                                                            <div
                                                                class="truncate text-sm text-warm-600"
                                                                x-show="courseClass.teacher_name"
                                                                x-text="`${courseClass.teacher_name}`"
                                                            ></div>
                                                        </div>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template
                                x-if="getClassTypes(item.course).length === 1"
                            >
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold text-warm-800"
                                    >
                                        班級：
                                    </label>
                                    <div
                                        class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
                                    >
                                        <template
                                            x-for="courseClass in item.course.classes"
                                            :key="courseClass.id"
                                        >
                                            <label
                                                class="flex cursor-pointer items-start rounded-lg border-2 bg-white p-3 transition hover:border-orange-300"
                                                :class="item.selectedClassId === courseClass.id ? 'border-orange-500 bg-orange-50' : 'border-warm-200'"
                                            >
                                                <input
                                                    type="radio"
                                                    :name="`class_${index}`"
                                                    :value="courseClass.id"
                                                    x-model.number="item.selectedClassId"
                                                    class="mt-1 mr-3 h-5 w-5 cursor-pointer"
                                                />
                                                <div class="min-w-0 flex-1">
                                                    <div
                                                        class="font-semibold text-warm-900"
                                                        x-text="courseClass.code"
                                                    ></div>
                                                    <div
                                                        class="text-sm text-warm-600"
                                                        x-show="courseClass.start_time"
                                                    >
                                                        <span
                                                            x-text="`${courseClass.start_time} - ${courseClass.end_time}`"
                                                        ></span>
                                                    </div>
                                                    <div
                                                        class="truncate text-sm text-warm-600"
                                                        x-show="courseClass.teacher_name"
                                                        x-text="`${courseClass.teacher_name}`"
                                                    ></div>
                                                </div>
                                            </label>
                                        </template>
                                    </div>
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
            @submit.prevent="submitForm"
            class="rounded-lg border border-warm-200 bg-white p-6"
        >
            @csrf
            @if (isset($schedule))
                @method('PUT')
            @endif

            <div class="mb-4">
                <label class="mb-2 block text-lg font-semibold text-warm-900">
                    課表名稱（可選）
                </label>
                <input
                    type="text"
                    x-model="scheduleName"
                    placeholder="例如：浣熊的課表"
                    class="w-full rounded-lg border-2 border-warm-300 px-4 py-3 focus:border-orange-500 focus:outline-none"
                />
            </div>

            <div class="flex gap-4">
                <button
                    type="submit"
                    :disabled="selectedItems.length === 0 || submitting"
                    :class="selectedItems.length === 0 || submitting ? 'opacity-50 cursor-not-allowed' : ''"
                    class="flex-1 rounded-lg bg-orange-500 py-3 text-lg font-bold text-white transition hover:bg-orange-600"
                >
                    <span x-show="!submitting">保存課表</span>
                    <span x-show="submitting">保存中...</span>
                </button>
                <a
                    href="{{ isset($schedule) ? route('schedules.show', $schedule) : route('schedules.create') }}"
                    class="flex-1 rounded-lg bg-warm-200 py-3 text-center text-lg font-bold text-warm-900 transition hover:bg-warm-300"
                >
                    取消
                </a>
            </div>
        </form>
    </div>

    <script>
        function scheduleEditor() {
            return {
                allCourses: @json($courses),
                schedule: @json($schedule ?? null),
                searchQuery: '',
                filteredCourses: [],
                showResults: false,
                selectedItems: [],
                scheduleName: '',
                submitting: false,

                init() {
                    // 如果正在編輯現有課表，加載現有數據
                    if (
                        this.schedule &&
                        this.schedule.items &&
                        this.schedule.items.length > 0
                    ) {
                        this.scheduleName = this.schedule.name || ''

                        // 為每個項目建立 selectedItem
                        this.schedule.items.forEach(item => {
                            const courseClass = item.course_class
                            const course = courseClass.course

                            // 從 allCourses 中找到對應的課程
                            const fullCourse = this.allCourses.find(
                                c => c.id === course.id
                            )
                            if (fullCourse) {
                                this.selectedItems.push({
                                    course: fullCourse,
                                    selectedClassId: courseClass.id,
                                })
                            }
                        })
                    }

                    // Close dropdown when clicking outside
                    document.addEventListener('click', e => {
                        if (!e.target.closest('.relative')) {
                            this.showResults = false
                        }
                    })
                },

                filterCourses() {
                    const query = this.searchQuery.trim().toLowerCase()

                    if (!query) {
                        this.filteredCourses = []
                        this.showResults = false
                        return
                    }

                    this.filteredCourses = this.allCourses.filter(course =>
                        course.name.toLowerCase().includes(query)
                    )
                    this.showResults = true
                },

                selectCourse(course) {
                    if (
                        !this.selectedItems.some(
                            item => item.course.id === course.id
                        )
                    ) {
                        const selectedClassId =
                            course.classes.length === 1
                                ? course.classes[0].id
                                : course.classes.length > 0
                                  ? course.classes[0].id
                                  : null

                        this.selectedItems.push({
                            course: course,
                            selectedClassId: selectedClassId,
                        })
                    }
                    this.searchQuery = ''
                    this.filteredCourses = []
                    this.showResults = false
                },

                removeItem(index) {
                    this.selectedItems.splice(index, 1)
                },

                getClassTypes(course) {
                    const typeOrder = {
                        morning: 0,
                        afternoon: 1,
                        evening: 2,
                        full_remote: 3,
                    }
                    const types = [...new Set(course.classes.map(c => c.type))]
                    return types.sort(
                        (a, b) => (typeOrder[a] ?? 99) - (typeOrder[b] ?? 99)
                    )
                },

                getClassesByType(course, type) {
                    return course.classes.filter(c => c.type === type)
                },

                getTypeLabel(type) {
                    const labels = {
                        morning: '上午班',
                        afternoon: '下午班',
                        evening: '夜間班',
                        full_remote: '全遠距',
                    }
                    return labels[type] || type
                },

                async submitForm() {
                    if (this.selectedItems.length === 0) {
                        alert('請至少選擇一門課程')
                        return
                    }

                    const invalidItems = this.selectedItems.filter(
                        item => !item.selectedClassId
                    )
                    if (invalidItems.length > 0) {
                        alert('請為所有課程選擇班級')
                        return
                    }

                    this.submitting = true

                    try {
                        const isEdit = !!this.schedule
                        const url = isEdit
                            ? '{{ isset($schedule) ? route('schedules.update', $schedule) : '' }}'
                            : '{{ route('schedules.store') }}'
                        const method = isEdit ? 'PUT' : 'POST'

                        const response = await fetch(url, {
                            method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN':
                                    document.querySelector(
                                        'meta[name="csrf-token"]'
                                    )?.content || '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                name: this.scheduleName,
                                items: this.selectedItems.map(
                                    item => item.selectedClassId
                                ),
                            }),
                        })

                        if (response.ok) {
                            const result = await response.json()
                            window.location.href = result.redirect_url
                        } else {
                            const errors = await response.json()
                            let errorMsg = '保存失敗:\n'
                            if (errors.errors) {
                                Object.values(errors.errors).forEach(msgs => {
                                    errorMsg += msgs.join('\n') + '\n'
                                })
                            }
                            alert(errorMsg)
                        }
                    } catch (error) {
                        console.error('提交出錯:', error)
                        alert('保存失敗，請重試')
                    } finally {
                        this.submitting = false
                    }
                },
            }
        }
    </script>
@endsection
