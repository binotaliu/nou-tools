<script setup>
// 松鼠店長. A small SVG squirrel in an apron whose face follows the line's
// mood. Decorative: the dialogue box carries the words. Size it from the
// caller (`class="size-24"`).
defineProps({
  mood: { type: String, default: 'happy' },
})
</script>

<template>
  <svg
    viewBox="0 0 96 96"
    class="shrink-0"
    fill="none"
    aria-hidden="true"
    focusable="false"
    data-testid="cafe-barista-portrait"
  >
    <!-- tail -->
    <path
      d="M70 84c14-2 22-16 16-32-3-8-1-14 4-18-9-1-17 4-20 14-3 10-4 22 0 36Z"
      fill="#b9733a"
    />
    <path
      d="M78 76c6-6 8-14 5-22"
      stroke="#d99a5c"
      stroke-width="4"
      stroke-linecap="round"
    />
    <!-- body + apron -->
    <path d="M26 92c0-16 8-28 22-28s22 12 22 28Z" fill="#c98a46" />
    <path d="M36 92V72h24v20Z" fill="#f4efe6" />
    <path d="M40 72l-3-8h22l-3 8Z" fill="#e5ddcf" />
    <!-- ears -->
    <path d="M28 24 24 8l16 8Z" fill="#c98a46" />
    <path d="M68 24 72 8 56 16Z" fill="#c98a46" />
    <!-- head -->
    <circle cx="48" cy="40" r="22" fill="#d99a5c" />
    <ellipse cx="48" cy="48" rx="13" ry="10" fill="#f4e3c8" />
    <!-- eyes -->
    <template v-if="mood === 'surprised'">
      <circle cx="39" cy="36" r="3.5" fill="#3b2a1a" />
      <circle cx="57" cy="36" r="3.5" fill="#3b2a1a" />
    </template>
    <template v-else-if="mood === 'happy'">
      <path
        d="M35 37q4-5 8 0M53 37q4-5 8 0"
        stroke="#3b2a1a"
        stroke-width="2.4"
        stroke-linecap="round"
      />
    </template>
    <template v-else>
      <circle cx="39" cy="37" r="2.6" fill="#3b2a1a" />
      <circle cx="57" cy="37" r="2.6" fill="#3b2a1a" />
    </template>
    <!-- brows -->
    <path
      v-if="mood === 'worried'"
      d="M34 30l8 3M62 30l-8 3"
      stroke="#3b2a1a"
      stroke-width="2"
      stroke-linecap="round"
    />
    <path
      v-else-if="mood === 'question'"
      d="M52 29q5-3 9 0"
      stroke="#3b2a1a"
      stroke-width="2"
      stroke-linecap="round"
    />
    <path
      v-else-if="mood === 'thinking'"
      d="M34 31h9M53 33h9"
      stroke="#3b2a1a"
      stroke-width="2"
      stroke-linecap="round"
    />
    <!-- nose + mouth -->
    <ellipse cx="48" cy="45" rx="3" ry="2.2" fill="#3b2a1a" />
    <path
      v-if="mood === 'happy'"
      d="M42 50q6 6 12 0"
      stroke="#3b2a1a"
      stroke-width="2"
      stroke-linecap="round"
    />
    <path
      v-else-if="mood === 'worried'"
      d="M43 53q5-4 10 0"
      stroke="#3b2a1a"
      stroke-width="2"
      stroke-linecap="round"
    />
    <circle
      v-else-if="mood === 'surprised'"
      cx="48"
      cy="52"
      r="2.4"
      fill="#3b2a1a"
    />
    <path
      v-else
      d="M44 51.5h8"
      stroke="#3b2a1a"
      stroke-width="2"
      stroke-linecap="round"
    />
  </svg>
</template>
