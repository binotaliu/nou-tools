<script setup>
// The menu behind that icon, drawn from a real iOS 27 screenshot but
// shortened: it runs longer than the screen part shown here, and its extra
// rows (translate, content blocker, Safari extensions) come and go with the
// page and what the person has installed. 分享 opens the second group.
import Glyph from './Glyph.vue'
import PhoneFrame from './PhoneFrame.vue'

const rows = [
  { label: '翻譯網站⋯', y: 12 },
  { label: '加入書籤', y: 52 },
  { label: '將書籤加入檔案夾⋯', y: 68 },
  { label: '在網頁中尋找', y: 90 },
  { label: '網頁縮放', y: 106 },
]
const dividers = [21, 79]
</script>

<template>
  <PhoneFrame label="點開網址列的圖示後，選單中段的「分享」">
    <g
      class="fill-zinc-100 stroke-zinc-300 dark:fill-zinc-800 dark:stroke-zinc-600"
    >
      <circle cx="52" cy="142" r="14" />
      <rect x="72" y="128" width="136" height="28" rx="14" />
      <circle cx="228" cy="142" r="14" />
    </g>
    <g class="text-zinc-700 dark:text-zinc-200">
      <Glyph name="back" :cx="52" :cy="142" :size="12" />
      <Glyph name="pageMenu" :cx="91" :cy="142" :size="13" />
      <Glyph name="reload" :cx="190" :cy="142" :size="11" />
      <Glyph name="tabs" :cx="228" :cy="142" :size="13" />
    </g>

    <rect
      x="58"
      y="-40"
      width="150"
      height="160"
      rx="16"
      class="fill-white stroke-zinc-300 dark:fill-zinc-800 dark:stroke-zinc-600"
    />
    <line
      v-for="y in dividers"
      :key="y"
      x1="68"
      :x2="198"
      :y1="y"
      :y2="y"
      class="stroke-zinc-200 dark:stroke-zinc-700"
    />
    <rect
      x="64"
      y="25"
      width="138"
      height="22"
      rx="9"
      class="fill-theme-500/20 stroke-theme-600 dark:stroke-theme-400"
      stroke-width="1.5"
    />
    <Glyph
      name="share"
      :cx="77"
      :cy="36"
      :size="12"
      class="text-theme-800 dark:text-theme-200"
    />
    <text
      x="93"
      y="39.5"
      font-size="10"
      font-weight="700"
      class="fill-theme-800 dark:fill-theme-200"
    >
      分享
    </text>
    <template v-for="row in rows" :key="row.label">
      <rect
        x="72"
        :y="row.y - 5"
        width="10"
        height="10"
        rx="2.5"
        class="fill-zinc-300 dark:fill-zinc-600"
      />
      <text
        x="93"
        :y="row.y + 3.5"
        font-size="9"
        class="fill-zinc-500 dark:fill-zinc-400"
      >
        {{ row.label }}
      </text>
    </template>
  </PhoneFrame>
</template>
