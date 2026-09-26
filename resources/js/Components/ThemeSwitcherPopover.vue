<script setup>
// Header trigger that opens a panel with the appearance controls (ThemeSettings:
// mode, accent color, font size).
// Replaces the old icon-only cycle button.
import { onBeforeUnmount, onMounted, ref } from 'vue'
import Icon from './Icon.vue'
import ThemeSettings from './ThemeSettings.vue'

defineProps({
  // Where the panel opens: below the trigger (header) or above it, growing
  // from its left edge (the sidebar's footer, at the bottom of the screen).
  placement: { type: String, default: 'below' },
  // The adaptable nav renders two of these (tab bar, sidebar) and gives the
  // key only to the one for the current mode.
  accesskey: { type: String, default: '3' },
})

const open = ref(false)
const root = ref(null)

function close() {
  open.value = false
}

function toggle() {
  open.value = !open.value
}

function onDocumentClick(event) {
  if (open.value && root.value && !root.value.contains(event.target)) {
    close()
  }
}

function onKeydown(event) {
  if (open.value && event.key === 'Escape') {
    close()
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div ref="root" class="relative pl-2 print:hidden">
    <button
      type="button"
      class="inline-flex items-center justify-center rounded-md border border-theme-200 bg-white p-2 text-theme-700 transition hover:bg-theme-50 focus:ring-2 focus:ring-theme-500 focus:outline-none md:mr-2 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
      :aria-expanded="open.toString()"
      :accesskey="accesskey"
      data-testid="theme-switcher-toggle"
      @click="toggle()"
    >
      <span class="sr-only">外觀與文字大小設定</span>

      <Icon name="paint-brush" class="size-5" />
    </button>

    <div
      v-show="open"
      :class="
        placement === 'above'
          ? 'bottom-full left-2 mb-2'
          : 'top-full right-0 mt-2'
      "
      class="absolute z-10 w-72 rounded-md border border-theme-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
      <ThemeSettings />
    </div>
  </div>
</template>
