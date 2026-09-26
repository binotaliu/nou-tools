import { onMounted, ref } from 'vue'

// 'wide' applies the WCAG 1.4.12 text-spacing values (line height 1.5, letter
// spacing 0.12em, word spacing 0.16em, paragraph spacing 2em) through
// `html[data-text-spacing='wide']` in app.css. Persisted in localStorage; the
// anti-flash script in app.blade.php reads the same key before first paint.
// Module-level ref so the header popover and the 設定 page share one state.
export const TEXT_SPACINGS = [
  { value: 'default', label: '預設' },
  { value: 'wide', label: '寬鬆' },
]

const STORAGE_KEY = 'nou:text-spacing:v1'

function readStored() {
  try {
    return localStorage.getItem(STORAGE_KEY) === 'wide' ? 'wide' : 'default'
  } catch {
    return 'default'
  }
}

const textSpacing = ref(readStored())

export default function useTextSpacing() {
  function apply() {
    if (textSpacing.value === 'wide') {
      document.documentElement.dataset.textSpacing = 'wide'
    } else {
      delete document.documentElement.dataset.textSpacing
    }
  }

  function setTextSpacing(value) {
    textSpacing.value = value === 'wide' ? 'wide' : 'default'

    try {
      if (textSpacing.value === 'wide') {
        localStorage.setItem(STORAGE_KEY, 'wide')
      } else {
        localStorage.removeItem(STORAGE_KEY)
      }
    } catch {
      // Storage blocked: the choice still applies for this page view.
    }

    apply()
  }

  onMounted(apply)

  return { textSpacing, setTextSpacing }
}
