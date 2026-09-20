import { onMounted, ref } from 'vue'

// Swatch colors are fixed previews of each palette's 500-shade, independent
// of whichever accent is currently active — see resources/css/app.css for
// the actual --color-theme-* overrides these values mirror.
export const ACCENTS = [
  { value: 'warm', label: '暖橘', swatch: 'oklch(0.72 0.15 40)' },
  { value: 'ocean', label: '海藍', swatch: 'oklch(0.72 0.15 230)' },
  { value: 'forest', label: '森綠', swatch: 'oklch(0.72 0.15 150)' },
  { value: 'purple', label: '皇紫', swatch: 'oklch(0.72 0.15 300)' },
  { value: 'pink', label: '粉紅', swatch: 'oklch(0.72 0.15 350)' },
  { value: 'red', label: '緋紅', swatch: 'oklch(0.72 0.15 20)' },
  { value: 'grey', label: '銀灰', swatch: 'oklch(0.72 0.02 250)' },
]

// Persisted in localStorage, applied via a `data-accent` attribute on <html>
// that resources/css/app.css matches to override --color-theme-*. The
// anti-flash-of-wrong-theme inline script in app.blade.php reads the same
// localStorage key on first paint, so this only needs to keep it in sync
// after mount. The ref is module-level so every consumer (the header popover
// and the 設定 page are mounted together) sees the same selection.
const accent = ref(
  (typeof localStorage !== 'undefined' &&
    localStorage.getItem('accent-color')) ||
    'warm'
)

export default function useAccentColor() {
  function apply() {
    document.documentElement.dataset.accent = accent.value
  }

  function setAccent(value) {
    accent.value = value
    localStorage.setItem('accent-color', accent.value)
    apply()
  }

  onMounted(() => {
    apply()
  })

  return { accent, setAccent }
}
