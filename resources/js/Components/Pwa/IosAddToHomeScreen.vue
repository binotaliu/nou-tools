<script setup>
// The confirmation sheet: keep the name, keep "open as web app" on (iOS 26)
// and tap 加入 in the top-right corner.
import PhoneFrame from './PhoneFrame.vue'
import TapMarker from './TapMarker.vue'
</script>

<template>
  <PhoneFrame label="「加入主畫面」確認視窗右上角的「加入」" :show-page="false">
    <rect
      x="30"
      y="-30"
      width="220"
      height="196"
      class="fill-zinc-100 dark:fill-zinc-800"
    />
    <text
      x="44"
      y="30"
      font-size="10"
      class="fill-theme-700 dark:fill-theme-300"
    >
      取消
    </text>
    <text
      x="140"
      y="30"
      font-size="10"
      font-weight="700"
      text-anchor="middle"
      class="fill-zinc-800 dark:fill-zinc-100"
    >
      加入主畫面
    </text>
    <TapMarker :cx="222" :cy="27" :r="15" />
    <text
      x="222"
      y="30"
      font-size="10"
      font-weight="700"
      text-anchor="middle"
      class="fill-theme-800 dark:fill-theme-200"
    >
      加入
    </text>

    <rect
      x="40"
      y="50"
      width="200"
      height="40"
      rx="10"
      class="fill-white dark:fill-zinc-700"
    />
    <rect x="48" y="58" width="24" height="24" rx="6" class="fill-theme-600" />
    <text x="82" y="74" font-size="10" class="fill-zinc-800 dark:fill-zinc-100">
      NOU 小幫手
    </text>

    <rect
      x="40"
      y="98"
      width="200"
      height="30"
      rx="10"
      class="fill-white dark:fill-zinc-700"
    />
    <text
      x="52"
      y="117"
      font-size="10"
      class="fill-zinc-800 dark:fill-zinc-100"
    >
      以網頁 App 開啟
    </text>
    <rect
      x="200"
      y="106"
      width="30"
      height="14"
      rx="7"
      class="fill-green-500"
    />
    <circle cx="223" cy="113" r="5.5" class="fill-white" />
  </PhoneFrame>
</template>
