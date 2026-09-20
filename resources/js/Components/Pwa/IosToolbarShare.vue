<script setup>
// iOS 15–18 Safari, and iOS 26's "Bottom"/"Top" layouts: a row of buttons
// under (or beside) the address bar, with Share in the middle.
import Glyph from './Glyph.vue'
import PhoneFrame from './PhoneFrame.vue'
import TapMarker from './TapMarker.vue'

const buttons = [
  { name: 'back', cx: 60 },
  { name: 'forward', cx: 100 },
  { name: 'share', cx: 140 },
  { name: 'book', cx: 180 },
  { name: 'tabs', cx: 220 },
]
</script>

<template>
  <PhoneFrame label="Safari 畫面下方工具列正中間的「分享」按鈕">
    <rect
      x="40"
      y="102"
      width="200"
      height="24"
      rx="12"
      class="fill-zinc-100 stroke-zinc-300 dark:fill-zinc-800 dark:stroke-zinc-600"
    />
    <rect
      x="104"
      y="112"
      width="72"
      height="4"
      rx="2"
      class="fill-zinc-300 dark:fill-zinc-600"
    />
    <g class="text-zinc-700 dark:text-zinc-200">
      <Glyph
        v-for="button in buttons"
        :key="button.name"
        :name="button.name"
        :cx="button.cx"
        :cy="144"
        :size="14"
      />
    </g>
    <TapMarker :cx="140" :cy="144" :r="15" />
  </PhoneFrame>
</template>
