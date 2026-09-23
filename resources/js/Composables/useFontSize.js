import { onMounted, ref } from 'vue'

// Scale factors are percentages of the browser's own default size, so a
// reader who already raised their browser font size keeps that on top of
// this. resources/css/app.css holds the matching `html[data-font-size]`
// rules (Tailwind sizes everything in rem, so scaling <html> scales the UI).
export const FONT_SIZES = [
  { value: 'default', label: '標準', scale: 1 },
  { value: 'large', label: '大', scale: 1.125 },
  { value: 'xlarge', label: '特大', scale: 1.25 },
  { value: 'xxlarge', label: '超大', scale: 1.5 },
]

// Persisted in localStorage and applied via a `data-font-size` attribute on
// <html>. The anti-flash script in app.blade.php reads the same key on first
// paint, so this only keeps it in sync after mount. Module-level ref so the
// header popover and the 設定 page share one selection.
function readStored() {
  try {
    const stored = localStorage.getItem('font-size')

    return FONT_SIZES.some(size => size.value === stored) ? stored : 'default'
  } catch {
    return 'default'
  }
}

const fontSize = ref(readStored())

export default function useFontSize() {
  function apply() {
    document.documentElement.dataset.fontSize = fontSize.value
  }

  function setFontSize(value) {
    fontSize.value = value

    try {
      localStorage.setItem('font-size', value)
    } catch {
      // Storage blocked: the choice still applies for this page view.
    }

    apply()
  }

  onMounted(() => {
    apply()
  })

  return { fontSize, setFontSize }
}
