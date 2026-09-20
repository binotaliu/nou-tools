import { onMounted, onUnmounted, ref } from 'vue'

// Reacts to the `beforeinstallprompt` capture and the
// `nou:install-prompt-ready` event wired up in app.js.
export default function usePwaInstallBanner() {
  const storageKey = 'pwa_install_banner_dismissed_v1'
  const visible = ref(false)
  const isIos = ref(false)
  // After "不再提示" the banner stays up once more to say where the install
  // instructions live (the footer), until the visitor closes it.
  const showDismissedNotice = ref(false)
  let suppressed = false

  function readDismissed() {
    try {
      return localStorage.getItem(storageKey) === '1'
    } catch {
      return false
    }
  }

  function onPromptReady() {
    if (!suppressed) {
      visible.value = true
    }
  }

  onMounted(() => {
    const isStandalone =
      window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true

    if (readDismissed() || isStandalone) {
      suppressed = true
      return
    }

    // iOS Safari never fires beforeinstallprompt, so it gets manual
    // "Add to Home Screen" instructions instead of an install button.
    isIos.value =
      /iphone|ipad|ipod/i.test(window.navigator.userAgent) && !window.MSStream

    if (window.__nouInstallPrompt || isIos.value) {
      visible.value = true
    }

    window.addEventListener('nou:install-prompt-ready', onPromptReady)
  })

  onUnmounted(() => {
    window.removeEventListener('nou:install-prompt-ready', onPromptReady)
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
      dismissForever()
    } else {
      close()
    }
  }

  // Hides the banner for this page view only; it comes back on the next visit.
  function close() {
    suppressed = true
    visible.value = false
  }

  // "不再提示": remembered in localStorage so a refresh keeps it hidden.
  function dismissForever() {
    suppressed = true

    try {
      localStorage.setItem(storageKey, '1')
    } catch {
      // Storage blocked (private mode etc.): fall back to this page view.
    }

    visible.value = false
  }

  // Same as dismissForever(), but keeps the banner up to point at the footer.
  function optOut() {
    dismissForever()
    showDismissedNotice.value = true
    visible.value = true
  }

  return {
    visible,
    isIos,
    showDismissedNotice,
    install,
    close,
    optOut,
  }
}
