<script setup>
// 包含面授課 checkbox shared by the stats modal and the progress page. The
// charts leave 面授 out by default (see useStudyChartFilter).
defineProps({
  modelValue: { type: Boolean, required: true },
  testid: { type: String, default: 'study-room-include-in-person' },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <label
    class="inline-flex items-center gap-2 text-sm text-theme-800 dark:text-zinc-200"
  >
    <input
      type="checkbox"
      class="size-4 rounded border-theme-300 text-theme-600 dark:border-zinc-600"
      :checked="modelValue"
      :data-testid="testid"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    包含面授課
  </label>
</template>
