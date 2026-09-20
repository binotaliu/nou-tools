<script setup>
// The share sheet after tapping 檢視較多 (drawn from a real screenshot,
// scrolled past the header): the grouped action list, with 加入主畫面 at the
// bottom of the first group.
import Glyph from './Glyph.vue'
import PhoneFrame from './PhoneFrame.vue'

const rows = [
  { label: '將書籤加入檔案夾⋯', y: 60 },
  { label: '加入喜好項目', y: 78 },
  { label: '在網頁中尋找', y: 96 },
]
const secondGroup = [
  { label: '標示', y: 150 },
  { label: '列印', y: 168 },
]
</script>

<template>
  <PhoneFrame label="展開後的分享選單，第一組動作裡的「加入主畫面」">
    <rect x="30" y="-30" width="220" height="196" class="fill-zinc-900/30" />
    <rect
      x="30"
      y="6"
      width="220"
      height="180"
      rx="18"
      class="fill-zinc-100 dark:fill-zinc-800"
    />

    <g v-for="cx in [60, 108, 156, 204]" :key="cx">
      <circle
        :cx="cx"
        cy="26"
        r="11"
        class="fill-zinc-200 dark:fill-zinc-600"
      />
      <rect
        :x="cx - 16"
        y="42"
        width="32"
        height="4"
        rx="2"
        class="fill-zinc-300 dark:fill-zinc-600"
      />
    </g>

    <rect
      x="38"
      y="52"
      width="204"
      height="82"
      rx="10"
      class="fill-white dark:fill-zinc-700"
    />
    <line
      v-for="y in [69, 87, 105]"
      :key="y"
      x1="62"
      x2="234"
      :y1="y"
      :y2="y"
      class="stroke-zinc-200 dark:stroke-zinc-600"
    />
    <rect
      x="42"
      y="108"
      width="196"
      height="22"
      rx="7"
      class="fill-theme-500/20 stroke-theme-600 dark:stroke-theme-400"
      stroke-width="1.5"
    />
    <Glyph
      name="plusSquare"
      :cx="52"
      :cy="119"
      :size="10"
      class="text-theme-800 dark:text-theme-200"
    />
    <text
      x="66"
      y="122.5"
      font-size="9"
      font-weight="700"
      class="fill-theme-800 dark:fill-theme-200"
    >
      加入主畫面
    </text>
    <template v-for="row in rows" :key="row.label">
      <rect
        x="47"
        :y="row.y - 5"
        width="10"
        height="10"
        rx="3"
        class="fill-zinc-300 dark:fill-zinc-500"
      />
      <text
        x="66"
        :y="row.y + 3.2"
        font-size="8.5"
        class="fill-zinc-600 dark:fill-zinc-300"
      >
        {{ row.label }}
      </text>
    </template>

    <rect
      x="38"
      y="140"
      width="204"
      height="50"
      rx="10"
      class="fill-white dark:fill-zinc-700"
    />
    <template v-for="row in secondGroup" :key="row.label">
      <rect
        x="47"
        :y="row.y - 5"
        width="10"
        height="10"
        rx="5"
        class="fill-zinc-300 dark:fill-zinc-500"
      />
      <text
        x="66"
        :y="row.y + 3.2"
        font-size="8.5"
        class="fill-zinc-600 dark:fill-zinc-300"
      >
        {{ row.label }}
      </text>
    </template>
  </PhoneFrame>
</template>
