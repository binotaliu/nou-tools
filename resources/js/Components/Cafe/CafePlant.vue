<script setup>
// Potted plants for the café floor. Decorative SVG like the study room's
// scenery; `variant` picks a tall floor plant, a small table plant or a
// bushy one for a corner. Colours come from the palette so they sit in both
// themes without separate dark-mode art.
defineProps({
  variant: { type: String, default: 'tall' },
})
</script>

<template>
  <svg
    v-if="variant === 'tall'"
    viewBox="0 0 60 100"
    class="h-full w-auto"
    aria-hidden="true"
    focusable="false"
  >
    <path d="M18 72h24l-3 26H21Z" class="fill-theme-500 dark:fill-theme-700" />
    <rect
      x="15"
      y="68"
      width="30"
      height="7"
      rx="2"
      class="fill-theme-600 dark:fill-theme-800"
    />
    <path
      d="M30 70V28"
      class="stroke-emerald-700"
      stroke-width="3"
      stroke-linecap="round"
    />
    <path
      d="M30 48C14 48 6 36 8 22c12 2 22 12 22 26Z"
      class="fill-emerald-500 dark:fill-emerald-600"
    />
    <path
      d="M30 40c16 0 24-12 22-26-12 2-22 12-22 26Z"
      class="fill-emerald-600 dark:fill-emerald-700"
    />
    <path
      d="M30 62C18 62 10 54 10 44c10 0 20 8 20 18Z"
      class="fill-emerald-600 dark:fill-emerald-700"
    />
    <path
      d="M30 56c12 0 20-8 20-18-10 0-20 8-20 18Z"
      class="fill-emerald-500 dark:fill-emerald-600"
    />
    <path
      d="M30 30c-2-10 2-18 8-24 2 8 0 18-8 24Z"
      class="fill-emerald-400 dark:fill-emerald-500"
    />
  </svg>

  <svg
    v-else-if="variant === 'bush'"
    viewBox="0 0 80 80"
    class="h-full w-auto"
    aria-hidden="true"
    focusable="false"
  >
    <path d="M22 56h36l-4 22H26Z" class="fill-theme-500 dark:fill-theme-700" />
    <rect
      x="18"
      y="52"
      width="44"
      height="7"
      rx="2"
      class="fill-theme-600 dark:fill-theme-800"
    />
    <circle
      cx="40"
      cy="36"
      r="20"
      class="fill-emerald-600 dark:fill-emerald-700"
    />
    <circle
      cx="26"
      cy="40"
      r="14"
      class="fill-emerald-500 dark:fill-emerald-600"
    />
    <circle
      cx="54"
      cy="38"
      r="15"
      class="fill-emerald-500 dark:fill-emerald-600"
    />
    <circle
      cx="40"
      cy="26"
      r="12"
      class="fill-emerald-400 dark:fill-emerald-500"
    />
    <circle cx="33" cy="33" r="2" class="fill-rose-300" />
    <circle cx="50" cy="44" r="2" class="fill-rose-300" />
    <circle cx="44" cy="22" r="2" class="fill-amber-200" />
  </svg>

  <svg
    v-else
    viewBox="0 0 40 40"
    class="h-full w-auto"
    aria-hidden="true"
    focusable="false"
  >
    <path d="M12 26h16l-2 12H14Z" class="fill-theme-500 dark:fill-theme-700" />
    <rect
      x="10"
      y="23"
      width="20"
      height="5"
      rx="1.5"
      class="fill-theme-600 dark:fill-theme-800"
    />
    <path
      d="M20 24c-8 0-12-6-10-14 6 2 10 8 10 14Z"
      class="fill-emerald-500 dark:fill-emerald-600"
    />
    <path
      d="M20 24c8 0 12-6 10-14-6 2-10 8-10 14Z"
      class="fill-emerald-600 dark:fill-emerald-700"
    />
    <path
      d="M20 22c-1-6 1-11 4-16 2 6 0 12-4 16Z"
      class="fill-emerald-400 dark:fill-emerald-500"
    />
  </svg>
</template>
