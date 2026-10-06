<script setup>
// A native time input with a clear button. Native time inputs can't be
// emptied once set on iOS Safari (and are awkward elsewhere), and an empty
// value is meaningful here: "no reminder that day".
defineProps({
  label: { type: String, required: true },
  inputClass: { type: String, default: '' },
  clearTestId: { type: String, default: undefined },
})

const model = defineModel({ type: String, default: '' })
</script>

<template>
  <div class="relative flex items-center">
    <input
      v-model="model"
      type="time"
      :aria-label="label"
      :class="[inputClass, model ? 'pr-8' : '']"
      v-bind="$attrs"
    />
    <button
      v-if="model"
      type="button"
      :aria-label="`清除${label}`"
      :data-testid="clearTestId"
      class="absolute right-1 flex size-6 items-center justify-center rounded-full text-theme-700 hover:bg-theme-100 dark:text-zinc-400 dark:hover:bg-zinc-700"
      @click="model = ''"
    >
      <span aria-hidden="true">×</span>
    </button>
  </div>
</template>

<script>
export default { inheritAttrs: false }
</script>
