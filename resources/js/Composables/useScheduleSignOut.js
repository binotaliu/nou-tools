import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { clearDeviceBackup } from './scheduleDeviceStorage'

// Signs this browser out of the remembered schedule. The server cannot know
// which push subscription belongs to this browser, so it is looked up here,
// sent along so the server drops it, and then unsubscribed locally too: a
// shared computer must not keep receiving someone else's reminders.
export default function useScheduleSignOut() {
  const processing = ref(false)

  async function currentSubscription() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      return null
    }

    try {
      const registration = await navigator.serviceWorker.ready

      return await registration.pushManager.getSubscription()
    } catch {
      return null
    }
  }

  async function signOut() {
    if (processing.value) {
      return
    }

    processing.value = true

    // Before the request, so the local copy can never sign this browser back
    // in even if the page is closed mid-way.
    clearDeviceBackup()

    const subscription = await currentSubscription()

    router.delete('/schedules/device', {
      data: { pushEndpoint: subscription?.endpoint ?? null },
      onSuccess: () => {
        subscription?.unsubscribe().catch(() => {})
      },
      onFinish: () => {
        processing.value = false
      },
    })
  }

  return { processing, signOut }
}
