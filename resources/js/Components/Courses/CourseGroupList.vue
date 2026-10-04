<script setup>
// Grouped course tables (desktop) and cards (phones). Plain links to each
// course by default; with `selectable` every row is a checkbox instead, used
// by the schedule editor's step 1.
import { Link } from '@inertiajs/vue3'
import { FIELD_META } from '../../Composables/useCourseFilters.js'

defineProps({
  sections: { type: Array, required: true },
  columns: { type: Array, required: true },
  selectable: { type: Boolean, default: false },
  selectedIds: { type: Array, default: () => [] },
  limitReached: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle'])

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
</script>

<template>
  <div
    v-for="section in sections"
    :key="section.key"
    class="mb-6 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
    :data-testid="'schedule-section-' + section.key"
  >
    <h2 class="mb-4 text-xl font-semibold text-theme-900 dark:text-zinc-100">
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
                  v-for="column in columns"
                  :key="column"
                  scope="col"
                  class="px-4 py-3 font-bold text-theme-900 dark:text-zinc-100"
                  :class="FIELD_META[column].thClass"
                >
                  {{ FIELD_META[column].title }}
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="course in group.courses"
                :key="course.id"
                class="border-b border-theme-200 hover:bg-theme-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
                :class="
                  selectable && selectedIds.includes(course.id)
                    ? 'bg-theme-50 dark:bg-zinc-950'
                    : ''
                "
              >
                <th
                  scope="row"
                  class="px-4 py-3 font-normal text-theme-800 dark:text-zinc-200"
                >
                  <label
                    v-if="selectable"
                    class="flex min-h-6 cursor-pointer items-center gap-3"
                  >
                    <input
                      type="checkbox"
                      class="size-5 shrink-0 rounded border-zinc-500 dark:border-zinc-500"
                      :data-testid="'course-checkbox-' + course.id"
                      :checked="selectedIds.includes(course.id)"
                      :disabled="
                        limitReached && !selectedIds.includes(course.id)
                      "
                      @change="emit('toggle', course)"
                    />
                    {{ course.name }}
                  </label>
                  <Link
                    v-else
                    :href="course.url"
                    class="inline-flex items-center gap-2 text-theme-700 underline underline-offset-4 hover:text-theme-800 hover:no-underline dark:text-theme-400 dark:hover:text-theme-300"
                  >
                    {{ course.name }}
                  </Link>
                </th>
                <td
                  v-for="column in columns"
                  :key="column"
                  class="px-4 py-3 text-theme-800 dark:text-zinc-200"
                  :class="FIELD_META[column].tdClass"
                >
                  {{ columnValue(course, column) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 手機版卡片列表 -->
        <div class="space-y-3 md:hidden" data-testid="schedule-mobile-cards">
          <component
            :is="selectable ? 'label' : 'div'"
            v-for="course in group.courses"
            :key="course.id"
            class="block rounded-lg border bg-white p-4 dark:bg-zinc-900"
            :class="[
              selectable ? 'flex cursor-pointer items-start gap-3' : '',
              selectable && selectedIds.includes(course.id)
                ? 'border-theme-500 bg-theme-50'
                : 'border-theme-200 dark:border-zinc-700',
            ]"
          >
            <input
              v-if="selectable"
              type="checkbox"
              class="mt-0.5 size-5 shrink-0 rounded border-zinc-500 dark:border-zinc-500"
              :data-testid="'course-checkbox-mobile-' + course.id"
              :checked="selectedIds.includes(course.id)"
              :disabled="limitReached && !selectedIds.includes(course.id)"
              @change="emit('toggle', course)"
            />
            <div class="min-w-0">
              <span
                v-if="selectable"
                class="text-base font-semibold text-theme-900 dark:text-zinc-100"
              >
                {{ course.name }}
              </span>
              <Link
                v-else
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
          </component>
        </div>
      </div>
    </div>
  </div>
</template>
