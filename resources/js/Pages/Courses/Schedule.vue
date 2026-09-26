<script setup>
// Filtering/grouping logic is inline here rather than as a shared
// composable, since it's only used on this one page — mirroring how
// Directory/Index.vue inlines its one-off Leaflet map logic.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import Select from '../../Components/Select.vue'

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

// Port of the Blade view's `$courseFrontEndData` @php block: flattens the
// exam-time groups and the micro-credit/remote courses into one list of
// plain course rows for client-side filtering/grouping.
const courses = computed(() => {
  const general = props.viewModel.groups.flatMap(group =>
    group.courses.map(course => ({
      id: course.id,
      name: course.name,
      department: course.department,
      credits: course.credits,
      section: 'general',
      examLabel: group.label,
      examWeekdayOrder: group.weekdayOrder,
      examTimeStart: group.examTimeStart,
      url: `/courses/${course.id}`,
    }))
  )

  const micro = props.viewModel.microCreditOrRemoteCourses.map(course => ({
    id: course.id,
    name: course.name,
    department: course.department,
    credits: course.credits,
    section: 'micro',
    examLabel: null,
    examWeekdayOrder: null,
    examTimeStart: null,
    url: `/courses/${course.id}`,
  }))

  return [...general, ...micro]
})

const departmentOptions = computed(() => {
  const values = new Set(
    courses.value.map(course => course.department).filter(Boolean)
  )

  return [...values].sort((a, b) => a.localeCompare(b, 'zh-Hant'))
})

const creditOptions = computed(() => {
  const values = new Set(
    courses.value
      .map(course => course.credits)
      .filter(credits => credits !== null && credits !== undefined)
  )

  return [...values].sort((a, b) => a - b)
})

function selectTerm(term) {
  router.get('/courses/schedule', { term }, { preserveScroll: true })
}

// --- courseSchedule() filtering/grouping state (port of course-schedule.js) ---
const search = ref('')
const department = ref([])
const credits = ref([])
const groupBy = ref('exam')

const fieldMeta = {
  department: { title: '學系', thClass: 'w-42', tdClass: '' },
  credits: {
    title: '學分',
    thClass: 'w-16 text-center',
    tdClass: 'text-center tabular-nums',
  },
  examLabel: {
    title: '考試時間',
    thClass: 'w-56',
    tdClass: '',
  },
}

const normalizedSearch = computed(() => search.value.trim().toLowerCase())

const filteredCourses = computed(() =>
  courses.value.filter(course => {
    const matchesSearch =
      normalizedSearch.value === '' ||
      course.name.toLowerCase().includes(normalizedSearch.value)
    const matchesDepartment =
      department.value.length === 0 ||
      department.value.includes(course.department)
    const matchesCredits =
      credits.value.length === 0 ||
      credits.value.includes(String(course.credits))

    return matchesSearch && matchesDepartment && matchesCredits
  })
)

const columns = computed(
  () =>
    ({
      exam: ['department', 'credits'],
      department: ['examLabel', 'credits'],
      credits: ['department', 'examLabel'],
    })[groupBy.value]
)

function sortByName(list) {
  return [...list].sort((a, b) => a.name.localeCompare(b.name, 'zh-Hant'))
}

function groupByExamTime(list) {
  const map = new Map()

  list.forEach(course => {
    const key = course.examLabel ?? '未排定考試時間'

    if (!map.has(key)) {
      map.set(key, {
        key,
        label: key,
        weekdayOrder: course.examWeekdayOrder ?? 99,
        examTimeStart: course.examTimeStart ?? '',
        courses: [],
      })
    }

    map.get(key).courses.push(course)
  })

  return [...map.values()]
    .map(group => ({ ...group, courses: sortByName(group.courses) }))
    .sort(
      (a, b) =>
        a.weekdayOrder - b.weekdayOrder ||
        a.examTimeStart.localeCompare(b.examTimeStart)
    )
}

function groupByField(list, keyFn, fallbackLabel, compare) {
  const map = new Map()

  list.forEach(course => {
    const value = keyFn(course)
    const key =
      value === null || value === undefined || value === '' ? '__none__' : value
    const label = key === '__none__' ? fallbackLabel : value

    if (!map.has(key)) {
      map.set(key, { key, label, sortValue: value, courses: [] })
    }

    map.get(key).courses.push(course)
  })

  return [...map.values()]
    .map(group => ({ ...group, courses: sortByName(group.courses) }))
    .sort((a, b) => {
      if (a.key === '__none__') {
        return 1
      }
      if (b.key === '__none__') {
        return -1
      }

      return compare(a.sortValue, b.sortValue)
    })
}

const sections = computed(() => {
  if (groupBy.value === 'exam') {
    const filtered = filteredCourses.value
    const general = filtered.filter(course => course.section === 'general')
    const micro = filtered.filter(course => course.section === 'micro')

    return [
      {
        key: 'general',
        title: '一般課程',
        groups: groupByExamTime(general),
        emptyMessage:
          courses.value.filter(course => course.section === 'general')
            .length === 0
            ? '目前查無考試時間資料。'
            : '目前沒有符合篩選條件的課程。',
      },
      {
        key: 'micro',
        title: '微學分與全遠距',
        groups: micro.length
          ? [{ key: 'micro', label: null, courses: sortByName(micro) }]
          : [],
        emptyMessage:
          courses.value.filter(course => course.section === 'micro').length ===
          0
            ? '目前查無微學分或全遠距課程。'
            : '目前沒有符合篩選條件的課程。',
      },
    ]
  }

  const filtered = filteredCourses.value
  const groups =
    groupBy.value === 'department'
      ? groupByField(
          filtered,
          course => course.department,
          '未分類學系',
          (a, b) => a.localeCompare(b, 'zh-Hant')
        )
      : groupByField(
          filtered,
          course =>
            course.credits === null || course.credits === undefined
              ? null
              : String(course.credits),
          '未標示學分',
          (a, b) => Number(a) - Number(b)
        )

  return [
    {
      key: groupBy.value,
      title: groupBy.value === 'department' ? '依學系分組' : '依學分數分組',
      groups,
      emptyMessage:
        courses.value.length === 0
          ? '目前查無課程資料。'
          : '目前沒有符合篩選條件的課程。',
    },
  ]
})

function columnValue(course, key) {
  if (key === 'department') {
    return course.department ?? '—'
  }
  if (key === 'credits') {
    return course.credits ?? '—'
  }

  return (
    course.examLabel ?? (course.section === 'micro' ? '微學分/全遠距' : '—')
  )
}

function mobileColumnValue(course, key) {
  const value = columnValue(course, key)

  return key === 'credits' &&
    course.credits !== null &&
    course.credits !== undefined
    ? `${value} 學分`
    : value
}

const hasFilters = computed(
  () => search.value || department.value.length || credits.value.length
)

function clearFilters() {
  search.value = ''
  department.value = []
  credits.value = []
}

// --- dropdown open/close state for the 學系/學分 multi-select panels ---
function useDropdown() {
  const open = ref(false)
  const el = ref(null)

  function handleDocumentClick(event) {
    if (open.value && el.value && !el.value.contains(event.target)) {
      open.value = false
    }
  }

  onMounted(() => document.addEventListener('click', handleDocumentClick))
  onUnmounted(() => document.removeEventListener('click', handleDocumentClick))

  return { open, el }
}

const departmentDropdown = useDropdown()
const creditsDropdown = useDropdown()
</script>

<template>
  <Head title="本學期開課表 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-5xl">
      <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="mb-2 text-3xl font-bold text-theme-900 dark:text-zinc-100">
            本學期開課表
          </h2>

          <div class="text-sm text-theme-700 dark:text-zinc-400">
            {{ toSemesterDisplay(viewModel.selectedTerm) }}
          </div>
        </div>

        <div class="w-full sm:w-auto sm:min-w-40">
          <Select
            id="term"
            name="term"
            aria-label="選擇學期"
            :model-value="viewModel.selectedTerm"
            @update:model-value="selectTerm"
          >
            <option
              v-for="term in viewModel.availableTerms"
              :key="term"
              :value="term"
            >
              {{ toSemesterDisplay(term) }}
            </option>
          </Select>
        </div>
      </div>

      <!-- Filters -->
      <div
        class="mb-6 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent>
          <div>
            <label
              for="search"
              aria-hidden="true"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              搜尋
            </label>
            <input
              id="search"
              v-model="search"
              type="text"
              name="search"
              aria-label="搜尋"
              accesskey="8"
              placeholder="課程名稱..."
              class="w-full rounded-lg border border-zinc-500 px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-500"
            />
          </div>
          <div>
            <label
              for="groupBy"
              aria-hidden="true"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              分組方式
            </label>
            <Select
              id="groupBy"
              v-model="groupBy"
              aria-label="分組方式"
              data-testid="group-by-select"
            >
              <option value="exam">考試時間</option>
              <option value="department">學系</option>
              <option value="credits">學分數</option>
            </Select>
          </div>
          <div>
            <label
              aria-hidden="true"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              學系
            </label>
            <div
              :ref="el => (departmentDropdown.el.value = el)"
              class="relative"
            >
              <button
                type="button"
                :aria-label="
                  '學系：' +
                  (department.length
                    ? '已選 ' + department.length + ' 項'
                    : '全部學系')
                "
                :aria-expanded="
                  departmentDropdown.open.value ? 'true' : 'false'
                "
                class="flex w-full items-center justify-between rounded-lg border border-zinc-500 px-3 py-2 text-left text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-500"
                @click="
                  departmentDropdown.open.value = !departmentDropdown.open.value
                "
              >
                <span>
                  {{
                    department.length
                      ? '已選 ' + department.length + ' 項'
                      : '全部學系'
                  }}
                </span>
                <Icon name="chevron-down" class="size-4 text-zinc-400" />
              </button>
              <div
                v-show="departmentDropdown.open.value"
                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
              >
                <label
                  v-for="departmentOption in departmentOptions"
                  :key="departmentOption"
                  class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-theme-50 dark:hover:bg-zinc-700"
                >
                  <input
                    v-model="department"
                    type="checkbox"
                    :value="departmentOption"
                    class="rounded border-zinc-500 dark:border-zinc-500"
                  />
                  {{ departmentOption }}
                </label>
              </div>
            </div>
          </div>
          <div>
            <label
              aria-hidden="true"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              學分
            </label>
            <div :ref="el => (creditsDropdown.el.value = el)" class="relative">
              <button
                type="button"
                :aria-label="
                  '學分：' +
                  (credits.length
                    ? '已選 ' + credits.length + ' 項'
                    : '全部學分')
                "
                :aria-expanded="creditsDropdown.open.value ? 'true' : 'false'"
                class="flex w-full items-center justify-between rounded-lg border border-zinc-500 px-3 py-2 text-left text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-500"
                @click="
                  creditsDropdown.open.value = !creditsDropdown.open.value
                "
              >
                <span>
                  {{
                    credits.length
                      ? '已選 ' + credits.length + ' 項'
                      : '全部學分'
                  }}
                </span>
                <Icon name="chevron-down" class="size-4 text-zinc-400" />
              </button>
              <div
                v-show="creditsDropdown.open.value"
                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-theme-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
              >
                <label
                  v-for="creditOption in creditOptions"
                  :key="creditOption"
                  class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-theme-50 dark:hover:bg-zinc-700"
                >
                  <input
                    v-model="credits"
                    type="checkbox"
                    :value="String(creditOption)"
                    class="rounded border-zinc-500 dark:border-zinc-500"
                  />
                  {{ creditOption }}
                </label>
              </div>
            </div>
          </div>
          <div class="flex items-end">
            <a
              v-show="hasFilters"
              href="#"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              @click.prevent="clearFilters()"
            >
              清除條件
            </a>
          </div>
        </form>
      </div>

      <div
        v-for="section in sections"
        :key="section.key"
        class="mb-6 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        :data-testid="'schedule-section-' + section.key"
      >
        <h2
          class="mb-4 text-xl font-semibold text-theme-900 dark:text-zinc-100"
        >
          {{ section.title }}
        </h2>

        <p
          v-if="section.groups.length === 0"
          class="text-sm text-theme-700 dark:text-zinc-400"
        >
          {{ section.emptyMessage }}
        </p>

        <div v-show="section.groups.length > 0" class="space-y-6">
          <div v-for="group in section.groups" :key="group.key">
            <h3
              v-show="group.label"
              class="mb-3 font-semibold text-theme-900 dark:text-zinc-100"
            >
              {{ group.label }}
            </h3>

            <!-- 桌面版表格 -->
            <div
              class="hidden overflow-x-auto md:block"
              data-testid="schedule-desktop-table"
            >
              <table
                class="w-full border-collapse overflow-hidden rounded text-left text-theme-700 dark:text-zinc-300"
              >
                <caption class="sr-only">
                  {{
                    group.label ? '課程列表－' + group.label : '課程列表'
                  }}
                </caption>
                <thead
                  class="border-b-2 border-theme-300 bg-theme-100 dark:border-zinc-600 dark:bg-zinc-900"
                >
                  <tr>
                    <th
                      scope="col"
                      class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                    >
                      課程名稱
                    </th>
                    <th
                      scope="col"
                      class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                      :class="fieldMeta[columns[0]].thClass"
                    >
                      {{ fieldMeta[columns[0]].title }}
                    </th>
                    <th
                      scope="col"
                      class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                      :class="fieldMeta[columns[1]].thClass"
                    >
                      {{ fieldMeta[columns[1]].title }}
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="course in group.courses"
                    :key="course.id"
                    class="border-b border-theme-200 hover:bg-theme-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
                  >
                    <th
                      scope="row"
                      class="px-4 py-3 font-normal text-theme-800 dark:text-zinc-200"
                    >
                      <Link
                        :href="course.url"
                        class="inline-flex items-center gap-2 text-theme-700 underline underline-offset-4 hover:text-theme-800 hover:no-underline dark:text-theme-400 dark:hover:text-theme-300"
                      >
                        {{ course.name }}
                      </Link>
                    </th>
                    <td
                      class="px-4 py-3 text-theme-800 dark:text-zinc-200"
                      :class="fieldMeta[columns[0]].tdClass"
                    >
                      {{ columnValue(course, columns[0]) }}
                    </td>
                    <td
                      class="px-4 py-3 text-theme-800 dark:text-zinc-200"
                      :class="fieldMeta[columns[1]].tdClass"
                    >
                      {{ columnValue(course, columns[1]) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- 手機版卡片列表 -->
            <div
              class="space-y-3 md:hidden"
              data-testid="schedule-mobile-cards"
            >
              <div
                v-for="course in group.courses"
                :key="course.id"
                class="rounded-lg border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
              >
                <Link
                  :href="course.url"
                  class="text-base font-semibold text-theme-700 underline underline-offset-4 hover:text-theme-800 hover:no-underline dark:text-theme-400 dark:hover:text-theme-300"
                >
                  {{ course.name }}
                </Link>
                <div
                  class="mt-1 flex items-center gap-2 text-sm text-theme-700 dark:text-zinc-400"
                >
                  <span>{{ mobileColumnValue(course, columns[0]) }}</span>
                  <span>·</span>
                  <span class="tabular-nums">{{
                    mobileColumnValue(course, columns[1])
                  }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
