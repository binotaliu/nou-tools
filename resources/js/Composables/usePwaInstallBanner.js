import { onMounted, ref } from 'vue'

// Reacts to the `beforeinstallprompt` capture and the
// `nou:install-prompt-ready` event wired up in app.js.
export default function usePwaInstallBanner() {
  const storageKey = 'pwa_install_banner_dismissed_v1'
  const visible = ref(false)
  const isIos = ref(false)

  onMounted(() => {
    const dismissed = localStorage.getItem(storageKey) === '1'
    const isStandalone =
      window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true

    if (dismissed || isStandalone) {
      return
    }

    // iOS Safari never fires beforeinstallprompt, so it gets manual
    // "Add to Home Screen" instructions instead of an install button.
    isIos.value =
      /iphone|ipad|ipod/i.test(window.navigator.userAgent) && !window.MSStream

    if (window.__nouInstallPrompt || isIos.value) {
      visible.value = true
    }

    window.addEventListener('nou:install-prompt-ready', () => {
      visible.value = true
    })
  })

  async function install() {
    const promptEvent = window.__nouInstallPrompt
    if (!promptEvent) {
      return
    }

    promptEvent.prompt()
    const { outcome } = await promptEvent.userChoice
    window.__nouInstallPrompt = null

    if (outcome === 'accepted') {
      dismiss()
    } else {
      visible.value = false
    }
  }

  function dismiss() {
    visible.value = false
    localStorage.setItem(storageKey, '1')
  }

  return { visible, isIos, install, dismiss }
}
