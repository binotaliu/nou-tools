<script setup>
// Bottom part of an iPhone (top cropped away, since every step of the
// install flow happens near the bottom edge), with a placeholder web page
// behind whatever the slot draws on the screen (viewBox 0 0 280 180,
// screen spans x 30–250 and ends at y 166).
import { useId } from 'vue'

defineProps({
  label: { type: String, required: true },
  showPage: { type: Boolean, default: true },
})

const clipId = useId()
</script>

<template>
  <svg
    viewBox="0 0 280 180"
    role="img"
    :aria-label="label"
    class="h-auto w-full"
  >
    <defs>
      <clipPath :id="clipId">
        <rect x="30" y="-30" width="220" height="196" rx="26" />
      </clipPath>
    </defs>
    <rect
      x="20"
      y="-40"
      width="240"
      height="216"
      rx="36"
      class="fill-zinc-800 dark:fill-zinc-600"
    />
    <g :clip-path="`url(#${clipId})`">
      <rect
        x="30"
        y="-30"
        width="220"
        height="196"
        class="fill-white dark:fill-zinc-900"
      />
      <g
        v-if="showPage"
        class="fill-zinc-200 dark:fill-zinc-700"
        aria-hidden="true"
      >
        <rect x="46" y="14" width="120" height="10" rx="5" />
        <rect x="46" y="34" width="188" height="6" rx="3" />
        <rect x="46" y="48" width="170" height="6" rx="3" />
        <rect x="46" y="62" width="188" height="6" rx="3" />
        <rect x="46" y="76" width="140" height="6" rx="3" />
        <rect x="46" y="96" width="188" height="6" rx="3" />
      </g>
      <slot />
      <rect
        x="110"
        y="160"
        width="60"
        height="3.5"
        rx="1.75"
        class="fill-zinc-900 dark:fill-zinc-100"
      />
    </g>
  </svg>
</template>
