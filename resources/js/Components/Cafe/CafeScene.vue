<script setup>
// The café's back wall: a window, the menu board and the espresso machine
// behind the counter. Pure CSS/SVG like the study room's scenery; steam is
// `animate-steam` and stops under reduced motion.
</script>

<template>
  <div
    class="relative overflow-hidden rounded-t-xl border-x-[6px] border-t-[6px] border-theme-300 bg-theme-100 px-4 pt-4 pb-0 dark:border-zinc-600 dark:bg-zinc-800"
    data-testid="cafe-scene"
    aria-hidden="true"
  >
    <div class="flex items-end justify-between gap-3">
      <!-- window -->
      <div
        class="h-20 w-28 shrink-0 rounded-md border-4 border-theme-300 bg-linear-to-b from-sky-200 to-sky-50 dark:border-zinc-600 dark:from-indigo-950 dark:to-slate-800"
      >
        <div
          class="h-full w-full border-x-2 border-theme-300/70 dark:border-zinc-600/70"
        ></div>
      </div>

      <!-- menu board -->
      <div
        class="-rotate-1 rounded-md border-4 border-amber-900/70 bg-zinc-800 px-3 py-2 text-center text-xs font-semibold text-amber-100 shadow-sm"
      >
        <p class="text-[0.6875rem] tracking-widest">今日菜單</p>
        <p class="mt-0.5 font-normal text-zinc-300">美式・拿鐵・抹茶拿鐵</p>
      </div>

      <!-- espresso machine -->
      <div class="relative shrink-0">
        <span
          v-for="n in 3"
          :key="n"
          class="absolute -top-3 h-3 w-1 animate-steam rounded-full bg-white/80 motion-reduce:animate-none dark:bg-zinc-300/60"
          :style="{ left: `${8 + n * 10}px`, animationDelay: `${n * 0.7}s` }"
        ></span>
        <svg viewBox="0 0 80 56" class="h-14 w-20" fill="none">
          <rect
            x="4"
            y="6"
            width="72"
            height="34"
            rx="5"
            class="fill-zinc-500 dark:fill-zinc-600"
          />
          <rect
            x="10"
            y="12"
            width="60"
            height="8"
            rx="2"
            class="fill-zinc-300 dark:fill-zinc-400"
          />
          <rect
            x="22"
            y="40"
            width="12"
            height="10"
            rx="1.5"
            class="fill-white dark:fill-zinc-200"
          />
          <rect
            x="46"
            y="40"
            width="12"
            height="10"
            rx="1.5"
            class="fill-white dark:fill-zinc-200"
          />
          <circle cx="62" cy="28" r="3" class="fill-amber-400" />
        </svg>
      </div>
    </div>

    <!-- counter top -->
    <div class="-mx-4 mt-3 h-3 bg-amber-900/70 dark:bg-amber-950"></div>
  </div>
</template>
