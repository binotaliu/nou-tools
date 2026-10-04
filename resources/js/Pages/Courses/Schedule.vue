<script setup>
import { computed, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Select from '../../Components/Select.vue'
import CourseFilters from '../../Components/Courses/CourseFilters.vue'
import CourseGroupList from '../../Components/Courses/CourseGroupList.vue'
import { toSemesterDisplay } from '../../Composables/semesterDisplay.js'
import { useCourseFilters } from '../../Composables/useCourseFilters.js'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

// Flattens the exam-time groups and the micro-credit/remote courses into one
// list of plain course rows for client-side filtering/grouping.
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

const filters = reactive(useCourseFilters(courses))

function selectTerm(term) {
  router.get('/courses/schedule', { term }, { preserveScroll: true })
}
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

      <CourseFilters :filters="filters" />

      <CourseGroupList
        :sections="filters.sections"
        :columns="filters.columns"
      />
    </div>
  </AppLayout>
</template>
