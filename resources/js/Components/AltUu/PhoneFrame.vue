<script setup>
// Bezel, notch and status bar shared by every drawn Alt UU screen. The frame
// is a fixed 256px wide so the screens inside can use tiny absolute type sizes;
// the hero scales whole phones with CSS transforms instead.
</script>

<template>
  <div
    class="relative aspect-[9/18.5] w-64 rounded-[2.4rem] border-[7px] border-zinc-900 bg-zinc-900 shadow-2xl ring-1 ring-zinc-700/50 dark:border-zinc-600 dark:bg-zinc-600 dark:ring-zinc-500/40"
  >
    <div
      class="flex h-full flex-col overflow-hidden rounded-[1.9rem] bg-theme-50 dark:bg-zinc-950"
    >
      <div class="relative flex items-center justify-between px-5 pt-2.5">
        <span
          class="text-[9px] font-semibold text-theme-900 dark:text-zinc-200"
        >
          9:41
        </span>
        <span
          class="absolute top-2 left-1/2 h-3.5 w-14 -translate-x-1/2 rounded-full bg-zinc-900"
        ></span>
        <span class="flex items-end gap-px">
          <span
            class="h-1 w-0.5 rounded-sm bg-theme-900 dark:bg-zinc-200"
          ></span>
          <span
            class="h-1.5 w-0.5 rounded-sm bg-theme-900 dark:bg-zinc-200"
          ></span>
          <span
            class="h-2 w-0.5 rounded-sm bg-theme-900 dark:bg-zinc-200"
          ></span>
          <span
            class="ml-1 h-1.5 w-3 rounded-[2px] bg-theme-900 dark:bg-zinc-200"
          ></span>
        </span>
      </div>

      <slot />
    </div>
  </div>
</template>
