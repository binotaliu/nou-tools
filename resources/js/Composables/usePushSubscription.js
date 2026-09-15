import { onMounted, ref } from 'vue'

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = atob(base64)

  return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)))
}

// Vue port of the `nouPushSubscription` Alpine.data() component
// (resources/js/alpine-components.js). `initial` mirrors the object the
// Blade x-data previously received: { vapidPublicKey, subscribeUrl,
// unsubscribeUrl }.
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
        enabled.value = !!subscription
      })
    )
  })

  function toggle() {
    return enabled.value ? disable() : enable()
  }

  async function enable() {
    if (busy.value) {
      return
    }

    busy.value = true

    try {
      const permission = await Notification.requestPermission()
      if (permission !== 'granted') {
        return
      }

      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(initial.vapidPublicKey),
      })

      await window.axios.post(initial.subscribeUrl, subscription.toJSON())

      enabled.value = true
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
      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.getSubscription()

      if (subscription) {
        await window.axios.delete(initial.unsubscribeUrl, {
          data: { endpoint: subscription.endpoint },
        })
        await subscription.unsubscribe()
      }

      enabled.value = false
    } finally {
      busy.value = false
    }
  }

  return { supported, enabled, busy, toggle, enable, disable }
}
