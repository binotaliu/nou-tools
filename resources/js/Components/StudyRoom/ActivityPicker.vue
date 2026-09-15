<script setup>
// Vue port of resources/views/study-room/partials/_activity-picker.blade.php:
// the activity radio-button group shared by the "start timer" form and the
// "change activity" modal (distinguished by `groupTestid`/`testidPrefix` so
// both can appear in the DOM at once without colliding data-testids).
import {
  AcademicCapIcon,
  ArrowPathIcon,
  BookOpenIcon,
  CheckCircleIcon,
  PencilSquareIcon,
  PresentationChartBarIcon,
} from '@heroicons/vue/24/outline'

defineProps({
  verbs: { type: Array, required: true },
  modelValue: { type: String, default: null },
  groupTestid: { type: String, default: 'study-room-verb-group' },
  testidPrefix: { type: String, default: 'study-room-verb-' },
  gridColsClass: { type: String, default: 'grid-cols-5' },
})

defineEmits(['update:modelValue'])

const VERB_ICONS = {
  exam_prep: AcademicCapIcon,
  reading: BookOpenIcon,
  homework: PencilSquareIcon,
  review: ArrowPathIcon,
  in_person_class: PresentationChartBarIcon,
}

function iconFor(value) {
  return VERB_ICONS[value] || CheckCircleIcon
}
</script>

<template>
  <div>
    <span
      class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
      >活動</span
    >
    <div
      class="grid gap-2"
      :class="gridColsClass"
      role="radiogroup"
      aria-label="活動"
      :data-testid="groupTestid"
    >
      <label
        v-for="verb in verbs"
        :key="verb.value"
        class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-warm-200 bg-white px-1 py-2.5 text-center transition has-checked:border-warm-700 has-checked:bg-warm-700 has-checked:text-white sm:gap-1.5 sm:px-3 sm:py-3 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:has-checked:border-warm-500 dark:has-checked:bg-warm-500 dark:has-checked:text-zinc-950"
      >
        <input
          type="radio"
          :value="verb.value"
          :checked="modelValue === verb.value"
          class="sr-only"
          :data-testid="testidPrefix + verb.value"
          @change="$emit('update:modelValue', verb.value)"
        />
        <component :is="iconFor(verb.value)" class="size-5 sm:size-6" />
        <span class="text-[11px] leading-tight font-medium sm:text-sm">{{
          verb.label
        }}</span>
      </label>
    </div>
  </div>
</template>
