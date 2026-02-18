@extends('layouts.app')

@section('title', '編輯課表 - 課程自主編排')

@section('content')
    <div x-data="scheduleEditor()" class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold text-warm-900 mb-6">編輯您的課表</h2>

        <!-- Search Section -->
        <div class="bg-white p-6 rounded-lg border border-warm-200 mb-8">
            <label class="block text-lg font-semibold text-warm-900 mb-3">搜尋課程</label>
            <div class="relative">
                <input
                    type="text"
                    x-model="searchQuery"
                    @input="searchCourses()"
                    placeholder="輸入課程名稱... (例如：數學、英文、物理)
                    class="w-full px-4 py-3 border-2 border-warm-300 rounded-lg focus:outline-none focus:border-orange-500 text-lg"
                    autocomplete="off"
                />
                <template x-if="searching">
                    <div class="absolute right-4 top-3 text-warm-600">🔄 搜尋中...</div>
                </template>
            </div>

            <!-- Search Results Dropdown -->
            <div x-show="showResults && courses.length > 0" class="absolute mt-2 w-full bg-white border border-warm-200 rounded-lg shadow-lg z-10 max-h-96 overflow-y-auto">
                <template x-for="course in courses" :key="course.id">
                    <div @click="selectCourse(course)" class="p-4 border-b border-warm-100 hover:bg-warm-50 cursor-pointer">
                        <div class="font-semibold text-warm-900" x-text="course.name"></div>
                        <div class="text-sm text-warm-600" x-text="`學期：${course.term}`"></div>
                    </div>
                </template>
            </div>

            <template x-if="showResults && courses.length === 0 && !searching && searchQuery">
                <div class="mt-2 p-4 bg-warm-50 border border-warm-200 rounded-lg text-warm-700">
                    找不到符合的課程。請試試其他關鍵字。
                </div>
            </template>
        </div>

        <!-- Selected Schedule Section -->
        <div class="bg-white p-6 rounded-lg border border-warm-200 mb-8">
            <h3 class="text-2xl font-bold text-warm-900 mb-4">您的課表</h3>

            <template x-if="selectedItems.length === 0">
                <div class="p-6 bg-warm-50 border-2 border-dashed border-warm-300 rounded-lg text-center text-warm-700">
                    <p class="text-lg">還沒有選擇任何課程。請在上方搜尋並選擇課程。</p>
                </div>
            </template>

            <div class="space-y-4">
                <template x-for="(item, index) in selectedItems" :key="index">
                    <div class="bg-warm-50 border-2 border-warm-300 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="text-lg font-bold text-warm-900" x-text="item.course.name"></div>
                                <div class="text-sm text-warm-600" x-text="`學期：${item.course.term}`"></div>
                            </div>
                            <button @click="removeItem(index)" class="bg-red-100 hover:bg-red-200 text-red-700 rounded px-3 py-1 text-sm font-semibold">
                                移除
                            </button>
                        </div>

                        <!-- Class Selection -->
                        <div class="mt-3">
                            <label class="block text-sm font-semibold text-warm-800 mb-2">選擇班級：</label>
                            <div class="space-y-2">
                                <template x-for="courseClass in item.course.classes" :key="courseClass.id">
                                    <label class="flex items-start p-3 bg-white border-2 rounded-lg cursor-pointer hover:border-orange-300 transition"
                                           :class="item.selectedClassId === courseClass.id ? 'border-orange-500 bg-orange-50' : 'border-warm-200'">
                                        <input
                                            type="radio"
                                            :name="`class_${index}`"
                                            :value="courseClass.id"
                                            x-model.number="item.selectedClassId"
                                            @change="updateSelectedClass(index, courseClass.id)"
                                            class="mt-1 mr-3 cursor-pointer w-5 h-5"
                                        />
                                        <div class="flex-1">
                                            <div class="font-semibold text-warm-900" x-text="courseClass.code"></div>
                                            <div class="text-sm text-warm-600">
                                                <span x-show="courseClass.start_time" x-text="`時間：${courseClass.start_time} - ${courseClass.end_time}`"></span>
                                            </div>
                                            <div class="text-sm text-warm-600" x-show="courseClass.teacher_name" x-text="`教師：${courseClass.teacher_name}`"></div>
                                            <div class="text-sm text-warm-600" x-show="courseClass.link">
                                                <a :href="courseClass.link" target="_blank" class="text-orange-600 hover:underline">📎 課程連結</a>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Submit Section -->
        <form @submit.prevent="submitForm" class="bg-white p-6 rounded-lg border border-warm-200">
            <div class="mb-4">
                <label class="block text-lg font-semibold text-warm-900 mb-2">課表名稱（可選）</label>
                <input
                    type="text"
                    x-model="scheduleName"
                    placeholder="例如：2026年春季課表"
                    class="w-full px-4 py-3 border-2 border-warm-300 rounded-lg focus:outline-none focus:border-orange-500"
                />
            </div>

            <div class="flex gap-4">
                <button
                    type="submit"
                    :disabled="selectedItems.length === 0 || submitting"
                    :class="selectedItems.length === 0 || submitting ? 'opacity-50 cursor-not-allowed' : ''"
                    class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg text-lg transition"
                >
                    <span x-show="!submitting">保存課表</span>
                    <span x-show="submitting">保存中...</span>
                </button>
                <a href="{{ route('schedule.index') }}" class="flex-1 bg-warm-200 hover:bg-warm-300 text-warm-900 font-bold py-3 rounded-lg text-lg text-center transition">
                    取消
                </a>
            </div>
        </form>
    </div>

    <script>
        function scheduleEditor() {
            return {
                searchQuery: '',
                courses: [],
                showResults: false,
                searching: false,
                selectedItems: [],
                scheduleName: '',
                submitting: false,
                searchTimeout: null,

                searchCourses() {
                    this.showResults = true;
                    clearTimeout(this.searchTimeout);

                    if (!this.searchQuery.trim()) {
                        this.courses = [];
                        return;
                    }

                    this.searching = true;
                    this.searchTimeout = setTimeout(() => {
                        fetch(`{{ route('api.courses.search') }}?q=${encodeURIComponent(this.searchQuery)}`)
                            .then(response => response.json())
                            .then(data => {
                                this.courses = data;
                                this.searching = false;
                            })
                            .catch(error => {
                                console.error('搜尋出錯:', error);
                                this.searching = false;
                            });
                    }, 300);
                },

                selectCourse(course) {
                    if (!this.selectedItems.some(item => item.course.id === course.id)) {
                        this.selectedItems.push({
                            course: course,
                            selectedClassId: course.classes.length > 0 ? course.classes[0].id : null,
                        });
                    }
                    this.searchQuery = '';
                    this.courses = [];
                    this.showResults = false;
                },

                removeItem(index) {
                    this.selectedItems.splice(index, 1);
                },

                updateSelectedClass(itemIndex, classId) {
                    this.selectedItems[itemIndex].selectedClassId = classId;
                },

                async submitForm() {
                    if (this.selectedItems.length === 0) {
                        alert('請至少選擇一門課程');
                        return;
                    }

                    const invalidItems = this.selectedItems.filter(item => !item.selectedClassId);
                    if (invalidItems.length > 0) {
                        alert('請為所有課程選擇班級');
                        return;
                    }

                    this.submitting = true;

                    try {
                        const response = await fetch('{{ route('schedule.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                name: this.scheduleName,
                                items: this.selectedItems.map(item => item.selectedClassId),
                            }),
                        });

                        if (response.ok) {
                            const result = await response.json();
                            window.location.href = result.redirect_url;
                        } else {
                            const errors = await response.json();
                            let errorMsg = '保存失敗:\n';
                            if (errors.errors) {
                                Object.values(errors.errors).forEach(msgs => {
                                    errorMsg += msgs.join('\n') + '\n';
                                });
                            }
                            alert(errorMsg);
                        }
                    } catch (error) {
                        console.error('提交出錯:', error);
                        alert('保存失敗，請重試');
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }
    </script>
@endsection
