<script setup>
// Radio group of a course's classes (班級), grouped by type when it has more
// than one. The v-model is the chosen class id.
import { computed } from 'vue'

const props = defineProps({
  course: { type: Object, required: true },
  name: { type: String, required: true },
})

const model = defineModel({ type: Number, default: null })

const TYPE_ORDER = { morning: 0, afternoon: 1, evening: 2, full_remote: 3 }
const TYPE_LABELS = {
  morning: '上午班',
  afternoon: '下午班',
  evening: '夜間班',
  full_remote: '全遠距',
}

const types = computed(() =>
  [...new Set(props.course.classes.map(c => c.type))].sort(
    (a, b) => (TYPE_ORDER[a] ?? 99) - (TYPE_ORDER[b] ?? 99)
  )
)

const isGrouped = computed(() => types.value.length > 1)

const groups = computed(() =>
  isGrouped.value
    ? types.value.map(type => ({
        type,
        label: TYPE_LABELS[type] || type,
        classes: props.course.classes.filter(c => c.type === type),
      }))
    : [{ type: null, label: null, classes: props.course.classes }]
)

// Mirrors the visible content of a class-option label (code/type label,
// tentative note, time range, teacher name) so the radio's aria-label
// carries the same information the sighted layout shows, instead of the
// class option's text content leaking into the tree as a duplicate node
// (see the aria-hidden wrapper below it).
function classOptionAriaLabel(courseClass) {
  const parts = [
    courseClass.is_tentative ? courseClass.type_label : courseClass.code,
  ]

  if (courseClass.is_tentative) {
    parts.push('尚未正式分班')
  }

  if (courseClass.start_time) {
    parts.push(`${courseClass.start_time} - ${courseClass.end_time}`)
  }

  if (courseClass.teacher_name) {
    parts.push(courseClass.teacher_name)
  }

  return parts.join(' ')
}
</script>

<template>
  <fieldset>
    <legend
      class="mb-2 text-sm font-semibold text-theme-800 dark:text-zinc-200"
    >
      {{ isGrouped ? '選擇班級：' : '班級：' }}
    </legend>

    <component
      :is="group.type ? 'fieldset' : 'div'"
      v-for="group in groups"
      :key="group.type ?? 'all'"
      :class="isGrouped ? 'mb-4' : ''"
    >
      <legend
        v-if="group.type"
        class="mb-2 text-sm font-semibold text-theme-700 dark:text-zinc-300"
      >
        {{ group.label }}
      </legend>
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
        <label
          v-for="courseClass in group.classes"
          :key="courseClass.id"
          :data-testid="
            courseClass.is_tentative
              ? 'tentative-session-' + courseClass.type
              : null
          "
          class="flex cursor-pointer items-start rounded-lg border-2 bg-white p-3 transition hover:border-theme-300 dark:bg-zinc-900"
          :class="
            model === courseClass.id
              ? 'border-theme-500 bg-theme-50'
              : 'border-theme-200 dark:border-zinc-700'
          "
        >
          <input
            v-model.number="model"
            type="radio"
            :name="name"
            :value="courseClass.id"
            :aria-label="classOptionAriaLabel(courseClass)"
            class="mt-1 mr-3 h-5 w-5 cursor-pointer"
          />
          <div aria-hidden="true" class="min-w-0 flex-1">
            <div class="font-semibold text-theme-900 dark:text-zinc-100">
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
                {{ courseClass.start_time }} - {{ courseClass.end_time }}
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
    </component>
  </fieldset>
</template>
