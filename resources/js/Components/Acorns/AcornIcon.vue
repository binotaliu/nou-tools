<script setup>
// There is no acorn emoji, so the currency gets its own small SVG. It takes
// its colour from the text colour for the cap and a fixed warm brown for the
// nut, so it reads on both themes.
defineProps({
  size: { type: String, default: 'size-4' },
})
</script>

<template>
  <svg
    :class="size"
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="true"
    focusable="false"
  >
    <path
      d="M12 21c-3.6 0-6.2-3.2-6.2-7.1 0-.6.5-1.1 1.1-1.1h10.2c.6 0 1.1.5 1.1 1.1C18.2 17.8 15.6 21 12 21Z"
      fill="#c98a46"
    />
    <path
      d="M4.5 11.5C4.5 8 7.6 5.6 12 5.6s7.5 2.4 7.5 5.9c0 .6-.5 1-1 1h-13c-.5 0-1-.4-1-1Z"
      fill="currentColor"
    />
    <path
      d="M12 5.6V3.2M12 3.2c.9-.5 1.9-.5 2.6 0"
      stroke="currentColor"
      stroke-width="1.5"
      stroke-linecap="round"
    />
  </svg>
</template>
