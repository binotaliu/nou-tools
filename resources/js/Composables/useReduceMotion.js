import { computed, onMounted, ref } from 'vue'

// 'system' follows the OS's prefers-reduced-motion; 'on' forces reduced
// motion for this browser. Persisted in localStorage and applied as
// `data-reduce-motion="true"` on <html> (app.css keys its global rule and the
// `motion-reduce:` variant off it). The anti-flash script in app.blade.php
// reads the same key before first paint. Module-level refs so the header
// popover, the 設定 page and JS-driven animations share one state.
const STORAGE_KEY = 'nou:reduce-motion:v1'

function readStored() {
  try {
    return localStorage.getItem(STORAGE_KEY) === 'on' ? 'on' : 'system'
  } catch {
    return 'system'
  }
}

const preference = ref(readStored())
const systemPrefersReduced = ref(false)
let queryBound = false

function bindSystemQuery() {
  if (queryBound || typeof window === 'undefined' || !window.matchMedia) {
    return
  }

  queryBound = true
  const query = window.matchMedia('(prefers-reduced-motion: reduce)')
  systemPrefersReduced.value = query.matches
  query.addEventListener('change', event => {
    systemPrefersReduced.value = event.matches
  })
}

bindSystemQuery()

// True when either the OS or the manual toggle asks for less motion.
export const prefersReducedMotion = computed(
  () => preference.value === 'on' || systemPrefersReduced.value
)

export default function useReduceMotion() {
  function apply() {
    if (preference.value === 'on') {
      document.documentElement.dataset.reduceMotion = 'true'
    } else {
      delete document.documentElement.dataset.reduceMotion
    }
  }

  function setReduceMotion(enabled) {
    preference.value = enabled ? 'on' : 'system'

    try {
      if (enabled) {
        localStorage.setItem(STORAGE_KEY, 'on')
      } else {
        localStorage.removeItem(STORAGE_KEY)
      }
    } catch {
      // Storage blocked: the choice still applies for this page view.
    }

    apply()
  }

  onMounted(apply)

  return {
    reduceMotion: computed(() => preference.value === 'on'),
    setReduceMotion,
    prefersReducedMotion,
  }
}
