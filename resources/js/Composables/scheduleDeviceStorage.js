// The browser-side copy of the device token (see useScheduleDeviceBackup).
// Storage can throw or be empty in private windows and with blocked site data,
// so every access is guarded and the app works without it.
export const STORAGE_KEY = 'schedule-device:v1'

export function readDeviceBackup() {
  try {
    const saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY))

    return saved && typeof saved.token === 'string' ? saved : null
  } catch {
    return null
  }
}

export function writeDeviceBackup(token, fingerprint) {
  try {
    window.localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({ token, fingerprint })
    )
  } catch {
    // Without storage the cookie alone still works.
  }
}

export function clearDeviceBackup() {
  try {
    window.localStorage.removeItem(STORAGE_KEY)
  } catch {
    // Nothing stored, nothing to clear.
  }
}
