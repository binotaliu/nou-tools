<script setup>
// The "tap here" highlight: a steady ring with a soft pulse around it.
// Pulse is skipped for people who ask for reduced motion.
defineProps({
  cx: { type: Number, required: true },
  cy: { type: Number, required: true },
  r: { type: Number, default: 16 },
})
</script>

<template>
  <g>
    <circle
      :cx="cx"
      :cy="cy"
      :r="r"
      class="origin-center animate-ping fill-theme-500/30 transform-fill motion-reduce:hidden"
    />
    <circle
      :cx="cx"
      :cy="cy"
      :r="r"
      class="fill-theme-500/15 stroke-theme-600 dark:stroke-theme-400"
      stroke-width="2"
    />
  </g>
</template>
