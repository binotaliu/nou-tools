import { onBeforeUnmount, onMounted, ref } from 'vue'

// State for the standalone install-instructions page: which device the
// visitor is on, whether the browser offered a one-tap install (captured in
// app.js as window.__nouInstallPrompt, possibly after this page has mounted),
// and whether the app is already installed.
export default function usePwaInstallPage() {
  const platform = ref('ios')
  const canInstall = ref(false)
  const isInstalled = ref(false)
  const justInstalled = ref(false)

  function syncPrompt() {
    canInstall.value = Boolean(window.__nouInstallPrompt)
    // Chrome on a Mac offers the same one-tap install; stay on its tab.
    if (canInstall.value && platform.value !== 'mac') {
      platform.value = 'android'
    }
  }

  onMounted(() => {
    isInstalled.value =
      window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true

    // iPadOS 13+ Safari identifies itself as a Mac, so also look for touch.
    const isIos =
      /iphone|ipad|ipod/i.test(window.navigator.userAgent) ||
      (window.navigator.platform === 'MacIntel' &&
        window.navigator.maxTouchPoints > 1)

    if (/android/i.test(window.navigator.userAgent)) {
      platform.value = 'android'
    } else if (isIos) {
      platform.value = 'ios'
    } else if (/macintosh/i.test(window.navigator.userAgent)) {
      platform.value = 'mac'
    }

    syncPrompt()
    window.addEventListener('nou:install-prompt-ready', syncPrompt)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('nou:install-prompt-ready', syncPrompt)
  })

  async function install() {
    const promptEvent = window.__nouInstallPrompt
    if (!promptEvent) {
      return
    }

    promptEvent.prompt()
    const { outcome } = await promptEvent.userChoice

    // The event can only be used once, accepted or not.
    window.__nouInstallPrompt = null
    canInstall.value = false
    justInstalled.value = outcome === 'accepted'
  }

  return { platform, canInstall, isInstalled, justInstalled, install }
}
