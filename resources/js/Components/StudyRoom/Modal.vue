<script setup>
// Used only by StudyRoom's page. Teleports to <body>, closes on Escape or a
// click on the backdrop.
defineProps({
  open: { type: Boolean, required: true },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  maxWidth: { type: String, default: 'max-w-md' },
})

const emit = defineEmits(['close'])

defineOptions({ inheritAttrs: false })

function close() {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div
      v-show="open"
      class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:items-center sm:p-0"
      @keydown.escape="close"
    >
      <div
        class="fixed inset-0 bg-black/40"
        aria-hidden="true"
        @click="close"
      ></div>

      <div
        role="dialog"
        aria-modal="true"
        class="relative max-h-[calc(100dvh-2rem)] w-full overflow-y-auto rounded-lg bg-white p-6 shadow-lg sm:max-h-[calc(100vh-2rem)] dark:bg-zinc-900"
        :class="maxWidth"
        v-bind="$attrs"
      >
        <h3
          v-if="title"
          class="mb-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
        >
          {{ title }}
        </h3>
        <p
          v-if="description"
          class="mb-4 text-sm text-warm-600 dark:text-zinc-400"
        >
          {{ description }}
        </p>

        <slot />

        <div v-if="$slots.footer" class="mt-4 flex justify-end">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>
