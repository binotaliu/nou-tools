<script setup>
// The light/dark/system tab-list, accent-color swatch picker and font-size
// picker. Shared by the header's ThemeSwitcherPopover and the 設定 page so both stay identical.
import Icon from './Icon.vue'
import useThemeSwitcher from '../Composables/useThemeSwitcher'
import useAccentColor, { ACCENTS } from '../Composables/useAccentColor'
import useFontSize, { FONT_SIZES } from '../Composables/useFontSize'
import useReduceMotion from '../Composables/useReduceMotion'
import useTextSpacing, { TEXT_SPACINGS } from '../Composables/useTextSpacing'

defineProps({
  // Bigger swatches for the full 設定 page; the header popover stays compact.
  large: { type: Boolean, default: false },
})

const { theme, setTheme } = useThemeSwitcher()
const { accent, setAccent } = useAccentColor()
const { fontSize, setFontSize } = useFontSize()
const { reduceMotion, setReduceMotion } = useReduceMotion()
const { textSpacing, setTextSpacing } = useTextSpacing()

const MODES = [
  { value: 'system', label: '系統' },
  { value: 'light', label: '淺色' },
  { value: 'dark', label: '深色' },
]
</script>

<template>
  <div class="space-y-4">
    <div>
      <p class="mb-2 text-xs font-medium text-theme-700 dark:text-zinc-400">
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
              : 'text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100'
          "
          data-analytics-event="theme_mode_change"
          data-analytics-feature="theme"
          :data-analytics-label="mode.value"
          @click="setTheme(mode.value)"
        >
          {{ mode.label }}
        </button>
      </div>
    </div>

    <div>
      <p class="mb-2 text-xs font-medium text-theme-700 dark:text-zinc-400">
        主題色
      </p>
      <div class="flex flex-wrap items-center justify-between gap-2">
        <button
          v-for="option in ACCENTS"
          :key="option.value"
          type="button"
          :aria-pressed="(accent === option.value).toString()"
          :aria-label="option.label"
          class="flex aspect-square shrink-0 items-center justify-center rounded ring-2 ring-offset-2 ring-offset-white transition dark:ring-offset-zinc-900"
          :class="[
            large ? 'size-8 sm:size-10' : 'size-6',
            accent === option.value
              ? 'ring-theme-500'
              : 'ring-transparent hover:ring-theme-200 dark:hover:ring-zinc-700',
          ]"
          :style="{ backgroundColor: option.swatch }"
          data-analytics-event="theme_accent_change"
          data-analytics-feature="theme"
          :data-analytics-label="option.value"
          @click="setAccent(option.value)"
        >
          <Icon
            v-if="accent === option.value"
            name="check"
            :class="large ? 'size-5 sm:size-6' : 'size-4'"
            class="text-white"
          />
        </button>
      </div>
    </div>

    <div>
      <p
        id="theme-font-size-label"
        class="mb-2 text-xs font-medium text-theme-700 dark:text-zinc-400"
      >
        文字大小
      </p>
      <div
        role="radiogroup"
        aria-labelledby="theme-font-size-label"
        class="grid grid-cols-4 gap-1 rounded-md bg-theme-100 p-1 dark:bg-zinc-800"
      >
        <button
          v-for="size in FONT_SIZES"
          :key="size.value"
          type="button"
          role="radio"
          :aria-checked="(fontSize === size.value).toString()"
          class="rounded px-1 py-1.5 text-sm font-medium transition-colors"
          :class="
            fontSize === size.value
              ? 'bg-white text-theme-900 shadow-sm dark:bg-zinc-700 dark:text-zinc-100'
              : 'text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100'
          "
          :data-testid="`font-size-${size.value}`"
          data-analytics-event="theme_font_size_change"
          data-analytics-feature="theme"
          :data-analytics-label="size.value"
          @click="setFontSize(size.value)"
        >
          {{ size.label }}
        </button>
      </div>
    </div>

    <div>
      <p
        id="theme-text-spacing-label"
        class="mb-2 text-xs font-medium text-theme-700 dark:text-zinc-400"
      >
        行距與字距
      </p>
      <div
        role="radiogroup"
        aria-labelledby="theme-text-spacing-label"
        class="grid grid-cols-2 gap-1 rounded-md bg-theme-100 p-1 dark:bg-zinc-800"
      >
        <button
          v-for="option in TEXT_SPACINGS"
          :key="option.value"
          type="button"
          role="radio"
          :aria-checked="(textSpacing === option.value).toString()"
          class="rounded px-1 py-1.5 text-sm font-medium transition-colors"
          :class="
            textSpacing === option.value
              ? 'bg-white text-theme-900 shadow-sm dark:bg-zinc-700 dark:text-zinc-100'
              : 'text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100'
          "
          :data-testid="`text-spacing-${option.value}`"
          data-analytics-event="theme_text_spacing_change"
          data-analytics-feature="theme"
          :data-analytics-label="option.value"
          @click="setTextSpacing(option.value)"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <div>
      <button
        type="button"
        role="switch"
        :aria-checked="reduceMotion.toString()"
        class="flex w-full items-center justify-between gap-3 rounded-md bg-theme-100 px-3 py-2 text-left text-sm font-medium text-theme-900 dark:bg-zinc-800 dark:text-zinc-100"
        data-testid="reduce-motion-toggle"
        data-analytics-event="theme_reduce_motion_change"
        data-analytics-feature="theme"
        :data-analytics-label="reduceMotion ? 'off' : 'on'"
        @click="setReduceMotion(!reduceMotion)"
      >
        減少動態效果
        <span
          aria-hidden="true"
          class="relative inline-block h-5 w-9 shrink-0 rounded-full transition-colors"
          :class="
            reduceMotion ? 'bg-theme-700' : 'bg-zinc-500 dark:bg-zinc-500'
          "
        >
          <span
            class="absolute top-0.5 left-0.5 size-4 rounded-full bg-white transition-transform"
            :class="reduceMotion ? 'translate-x-4' : ''"
          ></span>
        </span>
      </button>
      <p class="mt-1 text-xs text-theme-700 dark:text-zinc-400">
        預設跟隨系統設定；開啟後會停止動畫與平滑捲動。
      </p>
    </div>
  </div>
</template>
