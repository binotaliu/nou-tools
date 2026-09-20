<script setup>
// The share sheet as it first opens (drawn from a real screenshot): the site
// header, a row of apps and a row of round actions whose last button,
// 檢視較多, expands the full list.
import Glyph from './Glyph.vue'
import PhoneFrame from './PhoneFrame.vue'
import TapMarker from './TapMarker.vue'

const apps = [
  { label: 'News', x: 44, class: 'fill-rose-500' },
  { label: '提醒事項', x: 84, class: 'fill-zinc-300 dark:fill-zinc-500' },
  { label: '更多', x: 124, class: 'fill-white dark:fill-zinc-100' },
]
const actions = [
  { label: '拷貝', glyph: 'copy', cx: 60 },
  { label: '加入「書籤」', glyph: 'bookmark', cx: 108 },
  { label: '加入閱讀列表', glyph: 'glasses', cx: 156 },
  { label: '檢視較多', glyph: 'chevronDown', cx: 204 },
]
</script>

<template>
  <PhoneFrame label="分享選單裡，動作那排最右邊的「檢視較多」按鈕">
    <rect x="30" y="-30" width="220" height="196" class="fill-zinc-900/30" />
    <rect
      x="30"
      y="8"
      width="220"
      height="170"
      rx="18"
      class="fill-zinc-100 dark:fill-zinc-800"
    />

    <rect
      x="42"
      y="18"
      width="24"
      height="24"
      rx="7"
      class="fill-white dark:fill-zinc-700"
    />
    <circle
      cx="54"
      cy="30"
      r="6"
      class="fill-none stroke-zinc-400"
      stroke-width="1.5"
    />
    <text
      x="74"
      y="27"
      font-size="9"
      font-weight="700"
      class="fill-zinc-800 dark:fill-zinc-100"
    >
      NOU 小幫手
    </text>
    <rect
      x="74"
      y="31"
      width="40"
      height="4"
      rx="2"
      class="fill-zinc-300 dark:fill-zinc-600"
    />
    <rect
      x="74"
      y="38"
      width="36"
      height="11"
      rx="5.5"
      class="fill-white dark:fill-zinc-700"
    />
    <text
      x="92"
      y="46"
      font-size="6.5"
      text-anchor="middle"
      class="fill-zinc-700 dark:fill-zinc-200"
    >
      選項 ›
    </text>
    <line
      x1="42"
      x2="238"
      y1="56"
      y2="56"
      class="stroke-zinc-300 dark:stroke-zinc-600"
    />

    <g v-for="app in apps" :key="app.label">
      <rect
        :x="app.x"
        y="63"
        width="22"
        height="22"
        rx="6"
        :class="app.class"
      />
      <text
        :x="app.x + 11"
        y="95"
        font-size="5.5"
        text-anchor="middle"
        class="fill-zinc-600 dark:fill-zinc-300"
      >
        {{ app.label }}
      </text>
    </g>
    <line
      x1="42"
      x2="238"
      y1="102"
      y2="102"
      class="stroke-zinc-300 dark:stroke-zinc-600"
    />

    <g v-for="action in actions" :key="action.label">
      <circle
        :cx="action.cx"
        cy="122"
        r="13"
        class="fill-zinc-200 dark:fill-zinc-600"
      />
      <Glyph
        :name="action.glyph"
        :cx="action.cx"
        :cy="122"
        :size="12"
        class="text-zinc-800 dark:text-zinc-100"
      />
      <text
        :x="action.cx"
        y="144"
        font-size="5.5"
        text-anchor="middle"
        class="fill-zinc-600 dark:fill-zinc-300"
      >
        {{ action.label }}
      </text>
    </g>
    <TapMarker :cx="204" :cy="122" :r="15" />
  </PhoneFrame>
</template>
