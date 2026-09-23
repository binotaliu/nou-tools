<script setup>
// One line of text that scrolls like the seat status bubbles (the same
// `animate-marquee` slide over two copies) but only when it really doesn't
// fit. Unlike the bubbles' character-count check, this measures the box, so
// it is right for any width and for CJK titles. The truncated text stays in
// place (invisible while scrolling) as the accessible label and the
// prefers-reduced-motion fallback.
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  text: { type: String, required: true },
})

// Pixels per second, so long titles don't scroll faster than short ones.
const SCROLL_SPEED = 15
const GAP_PX = 24

const label = ref(null)
const overflowing = ref(false)
const durationSeconds = ref(12)

let observer = null

function measure() {
  if (!label.value) {
    return
  }

  overflowing.value = label.value.scrollWidth > label.value.clientWidth + 1
  durationSeconds.value = Math.max(
    10,
    (label.value.scrollWidth + GAP_PX) / SCROLL_SPEED
  )
}

onMounted(() => {
  measure()

  if (typeof ResizeObserver !== 'undefined') {
    observer = new ResizeObserver(measure)
    observer.observe(label.value)
  }
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
    observer = null
  }
})

watch(
  () => props.text,
  () => nextTick(measure)
)
</script>

<template>
  <span class="relative block overflow-hidden">
    <span
      ref="label"
      class="block truncate"
      :class="overflowing ? 'opacity-0 motion-reduce:opacity-100' : ''"
      >{{ text }}</span
    >
    <span
      v-if="overflowing"
      class="absolute inset-y-0 left-0 flex w-max animate-marquee whitespace-nowrap motion-reduce:hidden"
      :style="{ animationDuration: durationSeconds + 's' }"
      aria-hidden="true"
      data-testid="study-room-marquee"
    >
      <span class="pr-6">{{ text }}</span>
      <span class="pr-6" aria-hidden="true">{{ text }}</span>
    </span>
  </span>
</template>
