<script setup>
// Serves both the create ('/schedules/create') and edit
// ('/schedules/{schedule}/edit') routes, since
// ScheduleController::create()/edit() share this one view.
//
// The submit form is a real (non-Inertia) POST/PUT, not useForm(): the
// controller's store()/update() actions branch on `$request->wantsJson()`
// for a non-Inertia JSON API consumer (exercised directly by
// tests/Feature/ScheduleTest.php's postJson/putJson assertions), and
// Inertia's own XHR requests would risk tripping that branch. A native
// form submit (Accept: text/html) always takes the redirect path. Mirrors
// how LearningProgress/Show.vue's progress form avoids Inertia's request
// cycle for the same class of reason.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

// Port of the `Str::toSemesterDisplay()` macro (app/Providers/AppServiceProvider.php).
function toSemesterDisplay(semester) {
  const match = /^(\d{4})([ABC])$/.exec(semester ?? '')

  if (!match) {
    return semester
  }

  const rocYear = Number(match[1]) - 1911
  const termName = { A: '上學期', B: '下學期', C: '暑期' }[match[2]]

  return `${rocYear} 學年度${termName}`
}

const editing = computed(() => props.viewModel.scheduleUuid !== null)
const pageTitle = computed(() => (editing.value ? '編輯課表' : '新增課表'))
const headingText = computed(() =>
  editing.value ? '編輯您的課表' : '建立您的課表'
)
const submitLabel = computed(() => (editing.value ? '更新課表' : '建立課表'))
const submittingLabel = computed(() =>
  editing.value ? '更新中...' : '建立中...'
)

const formAction = computed(() =>
  editing.value ? `/schedules/${props.viewModel.scheduleUuid}` : '/schedules'
)

function selectTerm(term) {
  const url = editing.value
    ? `/schedules/${props.viewModel.scheduleUuid}/edit`
    : '/schedules/create'

  router.get(url, { term }, { preserveScroll: true })
}

// --- scheduleEditor() state (port of resources/js/schedule-editor.js) ---
const allCourses = props.viewModel.courses
const searchQuery = ref('')
const filteredCourses = ref([])
const showResults = ref(false)
const scheduleName = ref(props.viewModel.scheduleName ?? '')
const submitting = ref(false)

const selectedItems = ref(
  (props.viewModel.selectedItems ?? []).flatMap(item => {
    const fullCourse = allCourses.find(c => c.id === item.courseId)

    return fullCourse
      ? [{ course: fullCourse, selectedClassId: item.classId }]
      : []
  })
)

function filterCourses() {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) {
    filteredCourses.value = []
    showResults.value = false
    return
  }

  filteredCourses.value = allCourses.filter(course =>
    course.name.toLowerCase().includes(query)
  )
  showResults.value = true
}

function selectCourse(course) {
  if (selectedItems.value.length >= 14) {
    alert('最多只能選擇 14 門課程')
    return
  }

  if (!selectedItems.value.some(item => item.course.id === course.id)) {
    const selectedClassId =
      course.classes.length > 0 ? course.classes[0].id : null

    selectedItems.value.push({
      course,
      selectedClassId,
    })
  }

  searchQuery.value = ''
  filteredCourses.value = []
  showResults.value = false
}

function removeItem(index) {
  selectedItems.value.splice(index, 1)
}

const TYPE_ORDER = { morning: 0, afternoon: 1, evening: 2, full_remote: 3 }
const TYPE_LABELS = {
  morning: '上午班',
  afternoon: '下午班',
  evening: '夜間班',
  full_remote: '全遠距',
}

function getClassTypes(course) {
  const types = [...new Set(course.classes.map(c => c.type))]
  return types.sort((a, b) => (TYPE_ORDER[a] ?? 99) - (TYPE_ORDER[b] ?? 99))
}

function getClassesByType(course, type) {
  return course.classes.filter(c => c.type === type)
}

function getTypeLabel(type) {
  return TYPE_LABELS[type] || type
}

function closeDropdownOnOutsideClick(event) {
  if (!event.target.closest('.relative')) {
    showResults.value = false
  }
}

onMounted(() => document.addEventListener('click', closeDropdownOnOutsideClick))
onUnmounted(() =>
  document.removeEventListener('click', closeDropdownOnOutsideClick)
)

const formRef = ref(null)

function submitForm() {
  if (selectedItems.value.length === 0) {
    alert('請至少選擇一門課程')
    return
  }

  if (selectedItems.value.length > 14) {
    alert('最多只能選擇 14 門課程')
    return
  }

  const invalidItems = selectedItems.value.filter(
    item => item.course.has_classes && !item.selectedClassId
  )
  if (invalidItems.length > 0) {
    alert('請為所有課程選擇班級')
    return
  }

  submitting.value = true
  formRef.value.submit()
}

const csrfToken =
  typeof document !== 'undefined'
    ? (document.querySelector('meta[name="csrf-token"]')?.content ?? '')
    : ''
</script>

<template>
  <Head :title="`${pageTitle} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-5xl">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ headingText }}
        </h2>
        <div class="w-full sm:w-auto sm:min-w-40">
          <label for="term" class="sr-only">選擇學期</label>
          <div class="relative">
            <select
              id="term"
              name="term"
              aria-label="選擇學期"
              class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700 dark:bg-zinc-900"
              :value="viewModel.selectedTerm"
              @change="selectTerm($event.target.value)"
            >
              <option
                v-for="term in viewModel.availableTerms"
                :key="term"
                :value="term"
              >
                {{ toSemesterDisplay(term) }}
              </option>
            </select>
            <div
              class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
            >
              <Icon name="chevron-down" class="size-5 text-gray-400" />
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="viewModel.previousSchedule && !editing"
        class="mb-6 flex items-center justify-between gap-3 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-900 dark:border-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-200"
      >
        <div>
          <div class="font-medium">
            你曾建立過課表：
            <span class="text-theme-900 dark:text-zinc-100">
              {{ viewModel.previousSchedule.name || '（未命名）' }}
            </span>
            ，確定要繼續新增新課表嗎？
          </div>
        </div>
        <div class="flex gap-2">
          <Link
            :href="`/schedules/${viewModel.previousSchedule.token}`"
            class="rounded bg-yellow-400 px-4 py-2 font-semibold text-yellow-900 hover:bg-yellow-500 dark:bg-yellow-600 dark:text-yellow-950 dark:hover:bg-yellow-500"
            data-analytics-event="schedule_open_previous"
            data-analytics-feature="schedule"
          >
            檢視舊課表
          </Link>
        </div>
      </div>

      <!-- Search Section -->
      <div
        class="mb-8 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <label
          class="mb-1 block text-xl font-semibold text-theme-900 dark:text-zinc-100"
          for="course-search"
        >
          搜尋課程
        </label>
        <div class="relative">
          <input
            id="course-search"
            v-model="searchQuery"
            type="text"
            placeholder="輸入課程名稱..."
            class="w-full rounded-lg border-2 border-theme-300 px-4 py-3 text-lg focus:border-orange-500 focus:outline-none dark:border-zinc-600"
            autocomplete="off"
            :disabled="selectedItems.length >= 14"
            @input="filterCourses()"
          />
        </div>

        <div
          v-show="showResults && filteredCourses.length > 0"
          class="mt-2 max-h-96 overflow-y-auto rounded-lg border border-theme-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div
            v-for="course in filteredCourses"
            :key="course.id"
            :data-testid="'course-option-' + course.id"
            class="cursor-pointer border-b border-theme-100 p-4 hover:bg-theme-50 dark:border-zinc-800 dark:hover:bg-zinc-950"
            @click="selectCourse(course)"
          >
            <div class="font-semibold text-theme-900 dark:text-zinc-100">
              {{ course.name }}
            </div>
          </div>
        </div>

        <div
          v-if="
            showResults && filteredCourses.length === 0 && searchQuery.trim()
          "
          class="mt-2 rounded-lg border border-theme-200 bg-theme-50 p-4 text-theme-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300"
        >
          找不到符合的課程。請試試其他關鍵字。
        </div>
      </div>

      <!-- Selected Schedule Section -->
      <div
        class="mb-8 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="mb-4">
          <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
            您的課表
          </h2>
        </div>

        <div v-if="selectedItems.length === 0">
          <div
            class="rounded-lg border-2 border-dashed border-theme-300 bg-theme-50 p-6 text-center text-theme-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-300"
          >
            <p class="text-lg">還沒有選擇任何課程。請在上方搜尋並選擇課程。</p>
          </div>
        </div>

        <div class="space-y-4">
          <div
            v-for="(item, index) in selectedItems"
            :key="index"
            :data-testid="'selected-item-' + item.course.id"
            class="rounded-lg border-2 border-theme-300 bg-theme-50 p-4 dark:border-zinc-600 dark:bg-zinc-950"
          >
            <div class="mb-3 flex items-start justify-between">
              <div>
                <div
                  class="text-lg font-bold text-theme-900 dark:text-zinc-100"
                >
                  {{ item.course.name }}
                </div>
              </div>
              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-100 bg-red-100 px-3 py-1 text-sm font-semibold text-red-700 transition hover:bg-red-200"
                @click="removeItem(index)"
              >
                移除
              </button>
            </div>

            <div class="mt-3">
              <div
                v-if="!item.course.has_classes"
                data-testid="pending-class-note"
                class="rounded-lg border-2 border-dashed border-theme-300 bg-theme-100 p-3 text-sm text-theme-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"
              >
                尚未開課，選課後將自動列入課表，開課後請記得回來選擇班級。
              </div>

              <div v-else>
                <template v-if="getClassTypes(item.course).length > 1">
                  <fieldset class="mb-4">
                    <legend
                      class="mb-2 text-sm font-semibold text-theme-800 dark:text-zinc-200"
                    >
                      選擇班級：
                    </legend>

                    <fieldset
                      v-for="type in getClassTypes(item.course)"
                      :key="type"
                      class="mb-4"
                    >
                      <legend
                        class="mb-2 text-sm font-semibold text-theme-700 dark:text-zinc-300"
                      >
                        {{ getTypeLabel(type) }}
                      </legend>
                      <div
                        class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
                      >
                        <label
                          v-for="courseClass in getClassesByType(
                            item.course,
                            type
                          )"
                          :key="courseClass.id"
                          :data-testid="
                            courseClass.is_tentative
                              ? 'tentative-session-' + courseClass.type
                              : null
                          "
                          class="flex cursor-pointer items-start rounded-lg border-2 bg-white p-3 transition hover:border-orange-300 dark:bg-zinc-900"
                          :class="
                            item.selectedClassId === courseClass.id
                              ? 'border-orange-500 bg-orange-50'
                              : 'border-theme-200 dark:border-zinc-700'
                          "
                        >
                          <input
                            v-model.number="item.selectedClassId"
                            type="radio"
                            :name="'class_' + index"
                            :value="courseClass.id"
                            class="mt-1 mr-3 h-5 w-5 cursor-pointer"
                          />
                          <div class="min-w-0 flex-1">
                            <div
                              class="font-semibold text-theme-900 dark:text-zinc-100"
                            >
                              {{
                                courseClass.is_tentative
                                  ? courseClass.type_label
                                  : courseClass.code
                              }}
                            </div>
                            <div
                              v-if="courseClass.is_tentative"
                              class="text-xs font-semibold text-amber-700 dark:text-amber-400"
                            >
                              尚未正式分班
                            </div>
                            <div
                              v-if="courseClass.start_time"
                              class="text-sm text-theme-700 dark:text-zinc-400"
                            >
                              <span>
                                {{ courseClass.start_time }} -
                                {{ courseClass.end_time }}
                              </span>
                            </div>
                            <div
                              v-if="courseClass.teacher_name"
                              class="truncate text-sm text-theme-700 dark:text-zinc-400"
                            >
                              {{ courseClass.teacher_name }}
                            </div>
                          </div>
                        </label>
                      </div>
                    </fieldset>
                  </fieldset>
                </template>

                <template v-else>
                  <fieldset>
                    <legend
                      class="mb-2 text-sm font-semibold text-theme-800 dark:text-zinc-200"
                    >
                      班級：
                    </legend>
                    <div
                      class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
                    >
                      <label
                        v-for="courseClass in item.course.classes"
                        :key="courseClass.id"
                        :data-testid="
                          courseClass.is_tentative
                            ? 'tentative-session-' + courseClass.type
                            : null
                        "
                        class="flex cursor-pointer items-start rounded-lg border-2 bg-white p-3 transition hover:border-orange-300 dark:bg-zinc-900"
                        :class="
                          item.selectedClassId === courseClass.id
                            ? 'border-orange-500 bg-orange-50'
                            : 'border-theme-200 dark:border-zinc-700'
                        "
                      >
                        <input
                          v-model.number="item.selectedClassId"
                          type="radio"
                          :name="'class_' + index"
                          :value="courseClass.id"
                          class="mt-1 mr-3 h-5 w-5 cursor-pointer"
                        />
                        <div class="min-w-0 flex-1">
                          <div
                            class="font-semibold text-theme-900 dark:text-zinc-100"
                          >
                            {{
                              courseClass.is_tentative
                                ? courseClass.type_label
                                : courseClass.code
                            }}
                          </div>
                          <div
                            v-if="courseClass.is_tentative"
                            class="text-xs font-semibold text-amber-700 dark:text-amber-400"
                          >
                            尚未正式分班
                          </div>
                          <div
                            v-if="courseClass.start_time"
                            class="text-sm text-theme-700 dark:text-zinc-400"
                          >
                            <span>
                              {{ courseClass.start_time }} -
                              {{ courseClass.end_time }}
                            </span>
                          </div>
                          <div
                            v-if="courseClass.teacher_name"
                            class="truncate text-sm text-theme-700 dark:text-zinc-400"
                          >
                            {{ courseClass.teacher_name }}
                          </div>
                        </div>
                      </label>
                    </div>
                  </fieldset>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Section -->
      <form
        ref="formRef"
        :action="formAction"
        method="POST"
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        @submit.prevent="submitForm"
      >
        <input type="hidden" name="_token" :value="csrfToken" />
        <input v-if="editing" type="hidden" name="_method" value="PUT" />
        <input type="hidden" name="term" :value="viewModel.selectedTerm" />

        <div class="mb-4">
          <label
            class="mb-1 block text-xl font-semibold text-theme-900 dark:text-zinc-100"
            for="schedule-name"
          >
            課表名稱（可選）
          </label>
          <input
            id="schedule-name"
            v-model="scheduleName"
            type="text"
            name="name"
            placeholder="例如：浣熊的課表"
            class="w-full rounded-lg border-2 border-theme-300 px-4 py-3 focus:border-orange-500 focus:outline-none dark:border-zinc-600"
          />
        </div>

        <span v-for="(item, index) in selectedItems" :key="item.course.id">
          <input
            type="hidden"
            :name="'items[' + index + '][course_id]'"
            :value="item.course.id"
          />
          <input
            v-if="item.selectedClassId"
            type="hidden"
            :name="'items[' + index + '][class_id]'"
            :value="item.selectedClassId"
          />
        </span>

        <div class="flex gap-4">
          <button
            type="submit"
            data-testid="schedule-submit"
            :disabled="selectedItems.length === 0 || submitting"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-6 py-3 text-lg font-semibold text-white transition hover:bg-theme-800 disabled:bg-theme-400"
          >
            <span v-if="!submitting">{{ submitLabel }}</span>
            <span v-else>{{ submittingLabel }}</span>
          </button>
          <Link
            :href="
              editing
                ? `/schedules/${viewModel.scheduleUuid}`
                : '/schedules/create'
            "
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-6 py-3 text-lg font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            取消
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
