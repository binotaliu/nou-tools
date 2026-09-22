import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Shared by the cookie-consent banner and the About page's toggle. Each
// component gets its own instance, seeded from this page's own
// analyticsConsent prop (HandleInertiaRequests shares it on every request,
// so a fresh Inertia visit always carries the current server-side state —
// no cross-page store needed).
export default function useAnalyticsConsent() {
  const page = usePage()

  const granted = ref(page.props.analyticsConsent?.granted ?? false)
  const showBanner = ref(page.props.analyticsConsent?.showBanner ?? false)
  const error = ref('')

  async function setConsent(value, { fromBanner = false } = {}) {
    error.value = ''
    granted.value = value

    if (fromBanner) {
      showBanner.value = false
    }

    try {
      await window.axios.put('/analytics-consent', { granted: value })

      if (typeof window.gtag === 'function') {
        window.gtag('consent', 'update', {
          analytics_storage: value ? 'granted' : 'denied',
        })
      }
    } catch (requestError) {
      granted.value = !value

      if (fromBanner) {
        showBanner.value = true
      }

      error.value =
        requestError.response?.data?.message ?? '儲存失敗，請稍後再試。'
    }
  }

  return {
    granted,
    showBanner,
    error,
    accept: () => setConsent(true, { fromBanner: true }),
    decline: () => setConsent(false, { fromBanner: true }),
    toggle: () => setConsent(!granted.value),
  }
}
