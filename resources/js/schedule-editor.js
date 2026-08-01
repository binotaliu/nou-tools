export default function scheduleEditor(config) {
  return {
    allCourses: config.courses,
    schedule: config.schedule,
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
          const courseId = item.course_class
            ? item.course_class.course.id
            : item.course.id

          // 從 allCourses 中找到對應的課程
          const fullCourse = this.allCourses.find(c => c.id === courseId)
          if (fullCourse) {
            this.selectedItems.push({
              course: fullCourse,
              selectedClassId: item.course_class ? item.course_class.id : null,
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
      // enforce limit
      if (this.selectedItems.length >= 10) {
        alert('最多只能選擇 10 門課程')
        return
      }

      if (!this.selectedItems.some(item => item.course.id === course.id)) {
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
      return types.sort((a, b) => (typeOrder[a] ?? 99) - (typeOrder[b] ?? 99))
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

    submitForm() {
      if (this.selectedItems.length === 0) {
        alert('請至少選擇一門課程')
        return
      }

      if (this.selectedItems.length > 10) {
        alert('最多只能選擇 10 門課程')
        return
      }

      const invalidItems = this.selectedItems.filter(
        item => item.course.has_classes && !item.selectedClassId
      )
      if (invalidItems.length > 0) {
        alert('請為所有課程選擇班級')
        return
      }

      // let the browser handle the submission normally
      this.submitting = true
      this.$refs.form.submit()
    },
  }
}
