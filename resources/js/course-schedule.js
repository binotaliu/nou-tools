export default function courseSchedule(config) {
  return {
    courses: config.courses,
    search: '',
    department: [],
    credits: [],
    groupBy: 'exam',

    fieldMeta: {
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
    },

    get normalizedSearch() {
      return this.search.trim().toLowerCase()
    },

    get filteredCourses() {
      return this.courses.filter(course => {
        const matchesSearch =
          this.normalizedSearch === '' ||
          course.name.toLowerCase().includes(this.normalizedSearch)
        const matchesDepartment =
          this.department.length === 0 ||
          this.department.includes(course.department)
        const matchesCredits =
          this.credits.length === 0 ||
          this.credits.includes(String(course.credits))

        return matchesSearch && matchesDepartment && matchesCredits
      })
    },

    get columns() {
      return {
        exam: ['department', 'credits'],
        department: ['examLabel', 'credits'],
        credits: ['department', 'examLabel'],
      }[this.groupBy]
    },

    get sections() {
      if (this.groupBy === 'exam') {
        const filtered = this.filteredCourses
        const general = filtered.filter(course => course.section === 'general')
        const micro = filtered.filter(course => course.section === 'micro')

        return [
          {
            key: 'general',
            title: '一般課程',
            groups: this.groupByExamTime(general),
            emptyMessage:
              this.courses.filter(course => course.section === 'general')
                .length === 0
                ? '目前查無考試時間資料。'
                : '目前沒有符合篩選條件的課程。',
          },
          {
            key: 'micro',
            title: '微學分與全遠距',
            groups: micro.length
              ? [
                  {
                    key: 'micro',
                    label: null,
                    courses: this.sortByName(micro),
                  },
                ]
              : [],
            emptyMessage:
              this.courses.filter(course => course.section === 'micro')
                .length === 0
                ? '目前查無微學分或全遠距課程。'
                : '目前沒有符合篩選條件的課程。',
          },
        ]
      }

      const filtered = this.filteredCourses
      const groups =
        this.groupBy === 'department'
          ? this.groupByField(
              filtered,
              course => course.department,
              '未分類學系',
              (a, b) => a.localeCompare(b, 'zh-Hant')
            )
          : this.groupByField(
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
          key: this.groupBy,
          title: this.groupBy === 'department' ? '依學系分組' : '依學分數分組',
          groups,
          emptyMessage:
            this.courses.length === 0
              ? '目前查無課程資料。'
              : '目前沒有符合篩選條件的課程。',
        },
      ]
    },

    sortByName(courses) {
      return courses
        .slice()
        .sort((a, b) => a.name.localeCompare(b.name, 'zh-Hant'))
    },

    groupByExamTime(courses) {
      const map = new Map()

      courses.forEach(course => {
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

      return Array.from(map.values())
        .map(group => ({
          ...group,
          courses: this.sortByName(group.courses),
        }))
        .sort(
          (a, b) =>
            a.weekdayOrder - b.weekdayOrder ||
            a.examTimeStart.localeCompare(b.examTimeStart)
        )
    },

    groupByField(courses, keyFn, fallbackLabel, compare) {
      const map = new Map()

      courses.forEach(course => {
        const value = keyFn(course)
        const key =
          value === null || value === undefined || value === ''
            ? '__none__'
            : value
        const label = key === '__none__' ? fallbackLabel : value

        if (!map.has(key)) {
          map.set(key, {
            key,
            label,
            sortValue: value,
            courses: [],
          })
        }

        map.get(key).courses.push(course)
      })

      return Array.from(map.values())
        .map(group => ({
          ...group,
          courses: this.sortByName(group.courses),
        }))
        .sort((a, b) => {
          if (a.key === '__none__') {
            return 1
          }
          if (b.key === '__none__') {
            return -1
          }

          return compare(a.sortValue, b.sortValue)
        })
    },

    columnValue(course, key) {
      if (key === 'department') {
        return course.department ?? '—'
      }
      if (key === 'credits') {
        return course.credits ?? '—'
      }

      return (
        course.examLabel ?? (course.section === 'micro' ? '微學分/全遠距' : '—')
      )
    },

    mobileColumnValue(course, key) {
      const value = this.columnValue(course, key)

      return key === 'credits' &&
        course.credits !== null &&
        course.credits !== undefined
        ? `${value} 學分`
        : value
    },

    get hasFilters() {
      return this.search || this.department.length || this.credits.length
    },

    clearFilters() {
      this.search = ''
      this.department = []
      this.credits = []
    },
  }
}
