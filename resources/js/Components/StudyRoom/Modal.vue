<script setup>
// Used only by StudyRoom's page. Teleports (default: <body>), closes on
// Escape or a click on the backdrop. Focus moves into the dialog when it
// opens, Tab wraps inside it, and focus returns to the opener on close
// (useDialogFocus). StudyRoom's Show.vue overrides
// teleportTo to a target inside its Twemoji-observed root, since Twemoji's
// MutationObserver only watches that subtree and can't see content
// teleported straight to <body>.
import { ref, useId } from 'vue'
import useDialogFocus from '../../Composables/useDialogFocus'

const props = defineProps({
  open: { type: Boolean, required: true },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  maxWidth: { type: String, default: 'max-w-md' },
  teleportTo: { type: String, default: 'body' },
})

const emit = defineEmits(['close'])

defineOptions({ inheritAttrs: false })

function close() {
  emit('close')
}

const dialog = ref(null)
const titleId = useId()
const descriptionId = useId()
const { onKeydown } = useDialogFocus(dialog, () => props.open)
</script>

<template>
  <Teleport :to="teleportTo" defer>
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
        ref="dialog"
        role="dialog"
        aria-modal="true"
        tabindex="-1"
        :aria-labelledby="title ? titleId : null"
        :aria-describedby="description ? descriptionId : null"
        class="relative max-h-[calc(100dvh-2rem)] w-full overflow-y-auto rounded-lg bg-white p-6 shadow-lg focus:outline-none sm:max-h-[calc(100vh-2rem)] dark:bg-zinc-900"
        :class="maxWidth"
        v-bind="$attrs"
        @keydown="onKeydown"
      >
        <h3
          v-if="title"
          :id="titleId"
          class="mb-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
        >
          {{ title }}
        </h3>
        <p
          v-if="description"
          :id="descriptionId"
          class="mb-4 text-sm text-theme-700 dark:text-zinc-400"
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
