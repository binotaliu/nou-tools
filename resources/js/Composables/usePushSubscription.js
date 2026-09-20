import { onMounted, ref } from 'vue'

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = atob(base64)

  return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)))
}

// `initial` mirrors { vapidPublicKey, subscribeUrl, unsubscribeUrl, enabled }.
//
// The browser's push subscription is shared by every feature that pushes, so
// holding one does not mean this feature is on. `enabled` is the server's
// opt-in for this feature (omit it to fall back to "a subscription exists"),
// and `unsubscribeUrl` only clears that opt-in: the subscription is kept.
export default function usePushSubscription(initial) {
  const supported = ref(false)
  const enabled = ref(false)
  const busy = ref(false)

  onMounted(() => {
    supported.value =
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window

    if (!supported.value) {
      return
    }

    navigator.serviceWorker.ready.then(registration =>
      registration.pushManager.getSubscription().then(subscription => {
        enabled.value = !!subscription && (initial.enabled ?? true)
      })
    )
  })

  function toggle() {
    return enabled.value ? disable() : enable()
  }

  // Resolves to whether a subscription is now registered, so a caller that
  // renders the state as a control can put it back when permission is
  // refused or the subscribe call fails.
  async function enable() {
    if (busy.value) {
      return false
    }

    busy.value = true

    try {
      const permission = await Notification.requestPermission()
      if (permission !== 'granted') {
        return false
      }

      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(initial.vapidPublicKey),
      })

      await window.axios.post(initial.subscribeUrl, subscription.toJSON())

      enabled.value = true

      return true
    } catch (error) {
      return false
    } finally {
      busy.value = false
    }
  }

  async function disable() {
    if (busy.value) {
      return
    }

    busy.value = true

    try {
      await window.axios.delete(initial.unsubscribeUrl)

      enabled.value = false
    } finally {
      busy.value = false
    }
  }

  return { supported, enabled, busy, toggle, enable, disable }
}
