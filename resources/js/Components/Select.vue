<script setup>
// Thin styling wrapper around a native <select>: the appearance-none +
// overlaid chevron treatment used ad-hoc across pages (Courses/Schedule.vue's
// term/groupBy selects, Directory/Index.vue's mobile center select, ...).
// Stays a real <select> rather than a custom listbox, so native keyboard
// nav, form semantics and screen-reader behaviour are untouched — only the
// visuals move here. `$attrs` (id, name, aria-label, data-testid, ...) fall
// straight through onto the <select> itself.
import Icon from './Icon.vue'

defineOptions({ inheritAttrs: false })

const [model, modelModifiers] = defineModel({
  set(value) {
    return modelModifiers.number ? Number(value) : value
  },
})
</script>

<template>
  <div class="relative">
    <select
      v-model="model"
      v-bind="$attrs"
      class="block w-full appearance-none rounded-lg border border-zinc-500 bg-white px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-500 dark:bg-zinc-900"
    >
      <slot />
    </select>
    <div
      class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
    >
      <Icon name="chevron-down" class="size-5 text-zinc-400" />
    </div>
  </div>
</template>
