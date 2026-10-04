import { computed, ref } from 'vue'

export const FIELD_META = {
  department: { title: '學系', thClass: 'w-42', tdClass: '' },
  credits: {
    title: '學分',
    thClass: 'w-16 text-center',
    tdClass: 'text-center tabular-nums',
  },
  examLabel: { title: '考試時間', thClass: 'w-56', tdClass: '' },
}

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

// Search, 學系/學分 filters and grouping shared by 本學期開課表 and the
// schedule editor's course picker. `courses` is a ref of flat rows:
// { id, name, department, credits, section: 'general'|'micro', examLabel,
//   examWeekdayOrder, examTimeStart, ... }.
export function useCourseFilters(courses) {
  const search = ref('')
  const department = ref([])
  const credits = ref([])
  const groupBy = ref('exam')

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
        .filter(value => value !== null && value !== undefined)
    )

    return [...values].sort((a, b) => a - b)
  })

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

  const sections = computed(() => {
    const filtered = filteredCourses.value

    if (groupBy.value === 'exam') {
      const general = filtered.filter(course => course.section === 'general')
      const micro = filtered.filter(course => course.section === 'micro')
      const all = courses.value

      return [
        {
          key: 'general',
          title: '一般課程',
          groups: groupByExamTime(general),
          emptyMessage:
            all.filter(course => course.section === 'general').length === 0
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
            all.filter(course => course.section === 'micro').length === 0
              ? '目前查無微學分或全遠距課程。'
              : '目前沒有符合篩選條件的課程。',
        },
      ]
    }

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

  const hasFilters = computed(
    () => search.value || department.value.length || credits.value.length
  )

  function clearFilters() {
    search.value = ''
    department.value = []
    credits.value = []
  }

  return {
    search,
    department,
    credits,
    groupBy,
    departmentOptions,
    creditOptions,
    filteredCourses,
    columns,
    sections,
    hasFilters,
    clearFilters,
  }
}
