import { onMounted, ref } from 'vue'

export const NAV_STYLES = [
  { value: 'tabs', label: '頂端列' },
  { value: 'sidebar', label: '側邊欄' },
]

// How the installed PWA lays out its navigation from `md` up (AdaptableNav):
// a top tab bar or a sidebar. Persisted in localStorage and applied via a
// `data-nav-style` attribute on <html>; the anti-flash script in
// app.blade.php reads the same key on first paint (PWAs only), so this only
// keeps it in sync after mount. Module-level ref so the nav's toggle and the
// 設定 page share one selection.
function readStored() {
  try {
    return localStorage.getItem('nou:nav-style:v1') === 'sidebar'
      ? 'sidebar'
      : 'tabs'
  } catch {
    return 'tabs'
  }
}

const navStyle = ref(readStored())

export default function useNavStyle() {
  function apply() {
    document.documentElement.dataset.navStyle = navStyle.value
  }

  function setNavStyle(value) {
    navStyle.value = value === 'sidebar' ? 'sidebar' : 'tabs'

    try {
      localStorage.setItem('nou:nav-style:v1', navStyle.value)
    } catch {
      // Storage blocked: the choice still applies for this page view.
    }

    apply()
  }

  onMounted(() => {
    apply()
  })

  return { navStyle, setNavStyle }
}
