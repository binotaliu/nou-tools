<script setup>
// macOS Safari: the 檔案 menu in the menu bar, opened, with 新增到 Dock⋯
// highlighted. Drawn from the real menu but shortened (it also has open-file,
// save, import and export entries), keeping the order around the target.
import TapMarker from './TapMarker.vue'

const menuBar = [
  { label: 'Safari', x: 20, bold: true },
  { label: '檔案', x: 64, active: true },
  { label: '編輯', x: 99 },
  { label: '顯示方式', x: 134 },
  { label: '歷史記錄', x: 188 },
]
// Each title gets 8px of padding either side of its text, so the highlight
// pill is the text width (9.5px per character) plus 16.
const activeItem = menuBar.find(item => item.active)
const activePillX = activeItem.x - 8
const activePillWidth = activeItem.label.length * 9.5 + 16

const entries = [
  { label: '新增視窗' },
  { label: '新增分頁' },
  { label: '開啟檔案⋯' },
  { divider: true },
  { label: '關閉視窗' },
  { label: '關閉分頁' },
  { divider: true },
  { label: '分享', submenu: true },
  { label: '新增到 Dock⋯', target: true },
  { divider: true },
  { label: '列印⋯' },
]

const menuX = activePillX
const menuY = 22
const rowHeight = 16
const dividerHeight = 7

// Stack the rows top to bottom, so the menu height follows the list.
let cursor = menuY + 5
const rows = entries.map(entry => {
  const y = cursor
  cursor += entry.divider ? dividerHeight : rowHeight
  return { ...entry, y }
})
const menuHeight = cursor + 5 - menuY
const target = rows.find(row => row.target)
</script>

<template>
  <svg
    viewBox="0 0 400 190"
    role="img"
    aria-label="Mac 上 Safari 選單列的「檔案」選單，其中的「新增到 Dock⋯」"
    class="h-auto w-full"
  >
    <rect
      x="2"
      y="2"
      width="396"
      height="186"
      rx="10"
      class="fill-white stroke-zinc-300 dark:fill-zinc-900 dark:stroke-zinc-600"
      stroke-width="2"
    />
    <g class="fill-zinc-200 dark:fill-zinc-700" aria-hidden="true">
      <rect x="290" y="46" width="80" height="10" rx="5" />
      <rect x="290" y="68" width="88" height="6" rx="3" />
      <rect x="290" y="82" width="70" height="6" rx="3" />
      <rect x="290" y="96" width="88" height="6" rx="3" />
    </g>

    <path
      d="M2 12a10 10 0 0 1 10-10h376a10 10 0 0 1 10 10v10H2z"
      class="fill-zinc-100 dark:fill-zinc-800"
    />
    <rect
      :x="activePillX"
      y="4"
      :width="activePillWidth"
      height="16"
      rx="4"
      class="fill-theme-600 dark:fill-theme-500"
    />
    <text
      v-for="item in menuBar"
      :key="item.label"
      :x="item.x"
      y="15"
      font-size="9.5"
      :font-weight="item.bold ? 700 : 400"
      :class="item.active ? 'fill-white' : 'fill-zinc-700 dark:fill-zinc-200'"
    >
      {{ item.label }}
    </text>

    <rect
      :x="menuX"
      :y="menuY"
      width="170"
      :height="menuHeight"
      rx="8"
      class="fill-white stroke-zinc-300 dark:fill-zinc-800 dark:stroke-zinc-600"
    />
    <template v-for="(row, index) in rows" :key="index">
      <line
        v-if="row.divider"
        :x1="menuX + 8"
        :x2="menuX + 162"
        :y1="row.y + dividerHeight / 2"
        :y2="row.y + dividerHeight / 2"
        class="stroke-zinc-200 dark:stroke-zinc-700"
      />
      <template v-else>
        <rect
          v-if="row.target"
          :x="menuX + 4"
          :y="row.y"
          width="162"
          :height="rowHeight"
          rx="5"
          class="fill-theme-500/20 stroke-theme-600 dark:stroke-theme-400"
          stroke-width="1.5"
        />
        <text
          :x="menuX + 12"
          :y="row.y + 11"
          font-size="9.5"
          :font-weight="row.target ? 700 : 400"
          :class="
            row.target
              ? 'fill-theme-800 dark:fill-theme-200'
              : 'fill-zinc-700 dark:fill-zinc-200'
          "
        >
          {{ row.label }}
        </text>
        <path
          v-if="row.submenu"
          :d="`M${menuX + 153} ${row.y + 4.5}l4 3.5-4 3.5`"
          fill="none"
          stroke-width="1.5"
          stroke-linecap="round"
          stroke-linejoin="round"
          class="stroke-zinc-500 dark:stroke-zinc-400"
        />
      </template>
    </template>

    <TapMarker :cx="menuX + 140" :cy="target.y + rowHeight / 2" :r="8" />
  </svg>
</template>
