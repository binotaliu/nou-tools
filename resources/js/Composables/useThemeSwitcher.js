import { onMounted, ref } from 'vue'

// Cycles system -> light -> dark -> system, persisted in localStorage,
// toggling the `dark` class on <html>. The anti-flash-of-wrong-theme inline
// script in app.blade.php reads the same localStorage key on first paint, so
// this only needs to keep it in sync after mount.
export default function useThemeSwitcher() {
  const theme = ref(
    (typeof localStorage !== 'undefined' && localStorage.getItem('theme')) ||
      'system'
  )

  function apply() {
    const prefersDark = window.matchMedia(
      '(prefers-color-scheme: dark)'
    ).matches
    const isDark =
      theme.value === 'dark' || (theme.value === 'system' && prefersDark)
    document.documentElement.classList.toggle('dark', isDark)
  }

  function cycle() {
    theme.value =
      theme.value === 'system'
        ? 'light'
        : theme.value === 'light'
          ? 'dark'
          : 'system'
    localStorage.setItem('theme', theme.value)
    apply()
  }

  function setTheme(value) {
    theme.value = value
    localStorage.setItem('theme', theme.value)
    apply()
  }

  onMounted(() => {
    apply()
    window
      .matchMedia('(prefers-color-scheme: dark)')
      .addEventListener('change', () => {
        if (theme.value === 'system') {
          apply()
        }
      })
  })

  return { theme, cycle, setTheme }
}
