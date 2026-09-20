<script setup>
// iPadOS Safari: everything lives in one toolbar at the top, with Share on
// the right next to "new tab" and the tab overview.
import Glyph from './Glyph.vue'
import TapMarker from './TapMarker.vue'
</script>

<template>
  <svg
    viewBox="0 0 400 120"
    role="img"
    aria-label="iPad 上 Safari 畫面右上方工具列的「分享」按鈕"
    class="h-auto w-full"
  >
    <rect
      x="8"
      y="6"
      width="384"
      height="200"
      rx="24"
      class="fill-zinc-800 dark:fill-zinc-600"
    />
    <rect
      x="18"
      y="16"
      width="364"
      height="200"
      rx="14"
      class="fill-white dark:fill-zinc-900"
    />
    <rect
      x="18"
      y="16"
      width="364"
      height="40"
      rx="14"
      class="fill-zinc-100 dark:fill-zinc-800"
    />
    <rect
      x="18"
      y="40"
      width="364"
      height="16"
      class="fill-zinc-100 dark:fill-zinc-800"
    />
    <g class="fill-zinc-200 dark:fill-zinc-700" aria-hidden="true">
      <rect x="40" y="76" width="140" height="10" rx="5" />
      <rect x="40" y="96" width="320" height="6" rx="3" />
      <rect x="40" y="110" width="290" height="6" rx="3" />
    </g>
    <rect
      x="120"
      y="26"
      width="150"
      height="20"
      rx="10"
      class="fill-white stroke-zinc-300 dark:fill-zinc-900 dark:stroke-zinc-600"
    />
    <rect
      x="163"
      y="34"
      width="64"
      height="4"
      rx="2"
      class="fill-zinc-300 dark:fill-zinc-600"
    />
    <g class="text-zinc-700 dark:text-zinc-200">
      <Glyph name="sidebar" :cx="40" :cy="36" :size="13" />
      <Glyph name="back" :cx="72" :cy="36" :size="11" />
      <Glyph name="forward" :cx="92" :cy="36" :size="11" />
      <Glyph name="share" :cx="304" :cy="36" :size="13" />
      <Glyph name="plus" :cx="330" :cy="36" :size="13" />
      <Glyph name="tabs" :cx="356" :cy="36" :size="13" />
    </g>
    <TapMarker :cx="304" :cy="36" :r="14" />
  </svg>
</template>
