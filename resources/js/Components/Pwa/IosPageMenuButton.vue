<script setup>
// iOS 27 Safari: the bar at the bottom has back, the address pill and the
// tab overview. Share is no longer a button; it's in the menu behind the
// icon at the left end of the address pill.
import Glyph from './Glyph.vue'
import PhoneFrame from './PhoneFrame.vue'
import TapMarker from './TapMarker.vue'
</script>

<template>
  <PhoneFrame label="Safari 網址列裡面最左邊的圖示">
    <g
      class="fill-zinc-100 stroke-zinc-300 dark:fill-zinc-800 dark:stroke-zinc-600"
    >
      <circle cx="52" cy="142" r="14" />
      <rect x="72" y="128" width="136" height="28" rx="14" />
      <circle cx="228" cy="142" r="14" />
    </g>
    <rect
      x="116"
      y="140"
      width="56"
      height="4"
      rx="2"
      class="fill-zinc-300 dark:fill-zinc-600"
    />
    <g class="text-zinc-700 dark:text-zinc-200">
      <Glyph name="back" :cx="52" :cy="142" :size="12" />
      <Glyph name="pageMenu" :cx="91" :cy="142" :size="13" />
      <Glyph name="reload" :cx="190" :cy="142" :size="11" />
      <Glyph name="tabs" :cx="228" :cy="142" :size="13" />
    </g>
    <TapMarker :cx="91" :cy="142" :r="13" />
  </PhoneFrame>
</template>
