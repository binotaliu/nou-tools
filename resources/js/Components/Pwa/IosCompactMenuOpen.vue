<script setup>
// The ••• menu of iOS 26's Compact layout (drawn from a real screenshot):
// 分享 tops the first group.
import Glyph from './Glyph.vue'
import PhoneFrame from './PhoneFrame.vue'

const rows = [
  { label: '加入「書籤」', glyph: 'bookmark', y: 30 },
  { label: '將書籤加入檔案夾⋯', glyph: 'book', y: 45 },
  { label: '新增標籤頁', glyph: 'plus', y: 65 },
  { label: '新增私密標籤頁', y: 80 },
]
const shortcuts = [
  { label: '書籤', glyph: 'book', cx: 152 },
  { label: '所有標籤頁', glyph: 'tabs', cx: 210 },
]
const dividers = [54, 89]
</script>

<template>
  <PhoneFrame label="點開「•••」後，選單最上方的「分享」">
    <g
      class="fill-zinc-100 stroke-zinc-300 dark:fill-zinc-800 dark:stroke-zinc-600"
    >
      <circle cx="52" cy="142" r="14" />
      <rect x="72" y="128" width="136" height="28" rx="14" />
      <circle cx="228" cy="142" r="14" />
    </g>
    <g class="text-zinc-700 dark:text-zinc-200">
      <Glyph name="back" :cx="52" :cy="142" :size="12" />
      <Glyph name="dots" :cx="228" :cy="142" :size="14" />
    </g>

    <rect
      x="112"
      y="2"
      width="132"
      height="119"
      rx="14"
      class="fill-white stroke-zinc-300 dark:fill-zinc-800 dark:stroke-zinc-600"
    />
    <line
      v-for="y in dividers"
      :key="y"
      x1="120"
      x2="236"
      :y1="y"
      :y2="y"
      class="stroke-zinc-200 dark:stroke-zinc-700"
    />
    <rect
      x="117"
      y="8"
      width="122"
      height="15"
      rx="7"
      class="fill-theme-500/20 stroke-theme-600 dark:stroke-theme-400"
      stroke-width="1.5"
    />
    <Glyph
      name="share"
      :cx="128"
      :cy="15.5"
      :size="10"
      class="text-theme-800 dark:text-theme-200"
    />
    <text
      x="141"
      y="19"
      font-size="9"
      font-weight="700"
      class="fill-theme-800 dark:fill-theme-200"
    >
      分享
    </text>
    <template v-for="row in rows" :key="row.label">
      <Glyph
        v-if="row.glyph"
        :name="row.glyph"
        :cx="128"
        :cy="row.y"
        :size="10"
        class="text-zinc-600 dark:text-zinc-300"
      />
      <rect
        v-else
        x="123"
        :y="row.y - 5"
        width="10"
        height="10"
        rx="3"
        class="fill-zinc-300 dark:fill-zinc-600"
      />
      <text
        x="141"
        :y="row.y + 3.2"
        font-size="8.5"
        class="fill-zinc-600 dark:fill-zinc-300"
      >
        {{ row.label }}
      </text>
    </template>
    <g
      v-for="shortcut in shortcuts"
      :key="shortcut.label"
      class="text-zinc-700 dark:text-zinc-200"
    >
      <Glyph :name="shortcut.glyph" :cx="shortcut.cx" :cy="100" :size="11" />
      <text
        :x="shortcut.cx"
        y="115"
        font-size="7.5"
        text-anchor="middle"
        class="fill-zinc-600 dark:fill-zinc-300"
      >
        {{ shortcut.label }}
      </text>
    </g>
  </PhoneFrame>
</template>
