import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { prefersReducedMotion } from './useReduceMotion'

// Slide state and autoplay for the homepage hero. Autoplay never starts for
// visitors who prefer reduced motion, and it holds while the pointer or focus
// is inside the hero, the tab is hidden, or the visitor pressed pause (the
// pause control is what lets keyboard and screen-reader users stop moving
// content).
export default function useHeroCarousel({ count, interval = 6000 }) {
  const index = ref(0)
  const paused = ref(false)
  const held = ref(false)
  const reducedMotion = ref(false)
  let timer = null

  const playing = computed(() => !paused.value && !reducedMotion.value)

  function stop() {
    clearInterval(timer)
    timer = null
  }

  function start() {
    stop()

    if (playing.value && !held.value && !document.hidden) {
      timer = setInterval(next, interval)
    }
  }

  function goTo(target) {
    index.value = (target + count) % count
    start()
  }

  function next() {
    goTo(index.value + 1)
  }

  function previous() {
    goTo(index.value - 1)
  }

  function togglePaused() {
    paused.value = !paused.value
    start()
  }

  function hold() {
    held.value = true
    stop()
  }

  function release() {
    held.value = false
    start()
  }

  // Horizontal swipe on touch screens.
  let touchStartX = null

  function onTouchStart(event) {
    touchStartX = event.touches[0].clientX
  }

  function onTouchEnd(event) {
    if (touchStartX === null) {
      return
    }

    const delta = event.changedTouches[0].clientX - touchStartX
    touchStartX = null

    if (Math.abs(delta) > 48) {
      delta < 0 ? next() : previous()
    }
  }

  watch(prefersReducedMotion, value => {
    reducedMotion.value = value
    start()
  })

  onMounted(() => {
    reducedMotion.value = prefersReducedMotion.value
    document.addEventListener('visibilitychange', start)
    start()
  })

  onUnmounted(() => {
    stop()
    document.removeEventListener('visibilitychange', start)
  })

  return {
    index,
    playing,
    goTo,
    next,
    previous,
    togglePaused,
    hold,
    release,
    onTouchStart,
    onTouchEnd,
  }
}
