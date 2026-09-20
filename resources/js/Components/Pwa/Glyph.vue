<script setup>
// Tiny Safari-toolbar glyphs (24px grid) for the illustrations. Rendered as a
// <g>, so it only works inside an <svg>; the colour comes from currentColor.
const props = defineProps({
  name: { type: String, required: true },
  cx: { type: Number, required: true },
  cy: { type: Number, required: true },
  size: { type: Number, default: 14 },
})

const paths = {
  share:
    'M12 3v12M8 7l4-4 4 4M7 10h-.5A1.5 1.5 0 0 0 5 11.5v7A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5v-7a1.5 1.5 0 0 0-1.5-1.5H17',
  bookmark: 'M7 4h10a1 1 0 0 1 1 1v15l-6-4-6 4V5a1 1 0 0 1 1-1z',
  copy: 'M8 8V6a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2M5 9a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z',
  glasses:
    'M2 14a4 4 0 1 0 8 0 4 4 0 0 0-8 0M14 14a4 4 0 1 0 8 0 4 4 0 0 0-8 0M10 14h4',
  chevronDown: 'M6 9l6 6 6-6',
  plusSquare:
    'M7 4h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3zM12 8v8M8 12h8',
  back: 'M15 5l-7 7 7 7',
  forward: 'M9 5l7 7-7 7',
  book: 'M12 6.5C10 5 7 4.5 4 5v13c3-.5 6 0 8 1.5 2-1.5 5-2 8-1.5V5c-3-.5-6 0-8 1.5zM12 6.5v13',
  tabs: 'M8 8V6.5A1.5 1.5 0 0 1 9.5 5h8A1.5 1.5 0 0 1 19 6.5v8a1.5 1.5 0 0 1-1.5 1.5H16M5 9.5A1.5 1.5 0 0 1 6.5 8h8A1.5 1.5 0 0 1 16 9.5v8a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 17.5z',
  plus: 'M12 5v14M5 12h14',
  sidebar:
    'M6 5h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM9.5 5v14',
  reload: 'M19 12a7 7 0 1 1-2-4.9M19 4v4h-4',
}
</script>

<template>
  <g
    :transform="`translate(${props.cx - props.size / 2} ${props.cy - props.size / 2}) scale(${props.size / 24})`"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
  >
    <template v-if="props.name === 'pageMenu'">
      <path
        d="M4.5 3h6a1 1 0 0 1 1 1v2.2a1.6 1.6 0 1 0 0 3.2V11a1 1 0 0 1-1 1h-6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"
        fill="currentColor"
        stroke="none"
        opacity="0.45"
      />
      <path d="M4 16.5h16M4 21h9" />
    </template>
    <template v-else-if="props.name === 'dots'">
      <circle cx="5" cy="12" r="1.8" fill="currentColor" stroke="none" />
      <circle cx="12" cy="12" r="1.8" fill="currentColor" stroke="none" />
      <circle cx="19" cy="12" r="1.8" fill="currentColor" stroke="none" />
    </template>
    <path v-else :d="paths[props.name]" />
  </g>
</template>
