import { onBeforeUnmount, onMounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  STORAGE_KEY,
  clearDeviceBackup,
  readDeviceBackup,
  writeDeviceBackup,
} from './scheduleDeviceStorage'

const RESTORE_ATTEMPTED_KEY = 'schedule-device:restore-attempted'

// Keeps a copy of the device token in local storage so a browser that dropped
// the cookie (Safari's storage limits, eviction) but not the storage can sign
// itself back in. The cookie stays the authority: the server only reads it, and
// this copy is used solely to ask for the cookie back.
//
// The token lives in an HttpOnly cookie that script cannot read, so the page
// compares the server's public fingerprint with the one stored next to the
// copy and fetches the token only when they differ (first visit, new sign-in).
// Signing out revokes the token server-side and clears the copy first, so the
// copy can never bring a session back.
export default function useScheduleDeviceBackup() {
  const page = usePage()

  async function backUp() {
    try {
      const { data } = await window.axios.post('/schedules/device/backup')

      if (data?.token) {
        writeDeviceBackup(data.token, data.fingerprint)
      }
    } catch {
      // Offline or throttled; the next page view tries again.
    }
  }

  async function restore(saved) {
    // One attempt per tab session: if the cookie will not stick (cookies
    // blocked) reloading would otherwise loop.
    try {
      if (window.sessionStorage.getItem(RESTORE_ATTEMPTED_KEY)) {
        return
      }

      window.sessionStorage.setItem(RESTORE_ATTEMPTED_KEY, '1')
    } catch {
      return
    }

    try {
      await window.axios.post('/schedules/device/restore', {
        token: saved.token,
      })

      window.location.reload()
    } catch (error) {
      if (error.response?.status === 422) {
        clearDeviceBackup()
      }
    }
  }

  function sync() {
    const device = page.props.scheduleDevice

    if (!device) {
      return
    }

    const saved = readDeviceBackup()

    if (device.signedIn) {
      try {
        window.sessionStorage.removeItem(RESTORE_ATTEMPTED_KEY)
      } catch {
        // Not essential.
      }

      if (!device.fingerprint) {
        // Signed in for this session only: nothing may outlive it.
        clearDeviceBackup()
      } else if (saved?.fingerprint !== device.fingerprint) {
        backUp()
      }

      return
    }

    if (saved) {
      restore(saved)
    }
  }

  // Another tab signed out (it cleared the copy): follow without waiting for
  // the next request to find out.
  function onStorage(event) {
    if (
      event.key === STORAGE_KEY &&
      event.newValue === null &&
      page.props.scheduleDevice?.signedIn
    ) {
      router.reload()
    }
  }

  onMounted(() => {
    sync()
    window.addEventListener('storage', onStorage)

    // Installed apps can ask the browser not to evict the copy.
    if (
      (window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true) &&
      typeof navigator.storage?.persist === 'function'
    ) {
      navigator.storage.persist().catch(() => {})
    }
  })

  onBeforeUnmount(() => {
    window.removeEventListener('storage', onStorage)
  })

  watch(() => page.props.scheduleDevice, sync)
}
