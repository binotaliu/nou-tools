<script setup>
// iOS 26 Safari, "Compact" layout (the default): one floating bar at the
// bottom with back, the address pill and a ••• button. Share isn't on the
// bar any more; it lives in the ••• menu.
import Glyph from './Glyph.vue'
import PhoneFrame from './PhoneFrame.vue'
import TapMarker from './TapMarker.vue'
</script>

<template>
  <PhoneFrame label="Safari 底部工具列右側的「•••」按鈕">
    <g
      class="fill-zinc-100 stroke-zinc-300 dark:fill-zinc-800 dark:stroke-zinc-600"
    >
      <circle cx="52" cy="142" r="14" />
      <rect x="72" y="128" width="136" height="28" rx="14" />
      <circle cx="228" cy="142" r="14" />
    </g>
    <rect
      x="104"
      y="140"
      width="72"
      height="4"
      rx="2"
      class="fill-zinc-300 dark:fill-zinc-600"
    />
    <g class="text-zinc-700 dark:text-zinc-200">
      <Glyph name="back" :cx="52" :cy="142" :size="12" />
      <Glyph name="dots" :cx="228" :cy="142" :size="14" />
    </g>
    <TapMarker :cx="228" :cy="142" :r="17" />
  </PhoneFrame>
</template>
