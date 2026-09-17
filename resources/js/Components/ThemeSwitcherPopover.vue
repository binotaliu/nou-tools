<script setup>
// Header trigger that opens a panel with a light/dark/system tab-list and an
// accent-color swatch picker. Replaces the old icon-only cycle button.
import { onBeforeUnmount, onMounted, ref } from 'vue'
import Icon from './Icon.vue'
import useThemeSwitcher from '../Composables/useThemeSwitcher'
import useAccentColor, { ACCENTS } from '../Composables/useAccentColor'

const { theme, setTheme } = useThemeSwitcher()
const { accent, setAccent } = useAccentColor()

const MODES = [
  { value: 'system', label: '系統' },
  { value: 'light', label: '淺色' },
  { value: 'dark', label: '深色' },
]

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
      @click="toggle()"
    >
      <span class="sr-only">切換佈景主題</span>

      <Icon v-if="theme === 'light'" name="sun" class="size-5" />
      <Icon v-else-if="theme === 'dark'" name="moon" class="size-5" />
      <Icon v-else name="computer-desktop" class="size-5" />
    </button>

    <div
      v-show="open"
      class="absolute top-full right-0 z-10 mt-2 w-64 space-y-4 rounded-md border border-theme-200 bg-white p-3 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
      <div>
        <p class="mb-2 text-xs font-medium text-theme-600 dark:text-zinc-400">
          外觀模式
        </p>
        <div
          role="tablist"
          class="grid grid-cols-3 gap-1 rounded-md bg-theme-100 p-1 dark:bg-zinc-800"
        >
          <button
            v-for="mode in MODES"
            :key="mode.value"
            type="button"
            role="tab"
            :aria-selected="(theme === mode.value).toString()"
            class="rounded px-2 py-1.5 text-sm font-medium transition-colors"
            :class="
              theme === mode.value
                ? 'bg-white text-theme-900 shadow-sm dark:bg-zinc-700 dark:text-zinc-100'
                : 'text-theme-600 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100'
            "
            @click="setTheme(mode.value)"
          >
            {{ mode.label }}
          </button>
        </div>
      </div>

      <div>
        <p class="mb-2 text-xs font-medium text-theme-600 dark:text-zinc-400">
          主題色
        </p>
        <div class="flex items-center gap-3">
          <button
            v-for="option in ACCENTS"
            :key="option.value"
            type="button"
            :aria-pressed="(accent === option.value).toString()"
            :aria-label="option.label"
            class="flex size-8 items-center justify-center rounded-full ring-2 ring-offset-2 ring-offset-white transition dark:ring-offset-zinc-900"
            :class="
              accent === option.value
                ? 'ring-theme-500'
                : 'ring-transparent hover:ring-theme-200 dark:hover:ring-zinc-700'
            "
            :style="{ backgroundColor: option.swatch }"
            @click="setAccent(option.value)"
          >
            <Icon
              v-if="accent === option.value"
              name="check"
              class="size-4 text-white"
            />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
