import { ref } from 'vue'

// Hands the schedule's PDF to the native share sheet (列印 / 儲存到檔案 / ...)
// instead of navigating to it. An installed PWA has no browser chrome, and a
// same-origin PDF opens inside the app with no way back on iOS.
//
// `state`: 'idle' → 'loading' (fetching the PDF, which can take seconds when
// Chromium has to render it) → back to 'idle' once the sheet was shown or
// dismissed. 'ready' means the file is fetched but the browser refused
// share() because too long passed since the tap (iOS demands a fresh user
// gesture), so the page asks for one more tap that calls share(). 'failed'
// means the PDF could not be fetched or shared.

const isInstalledPwa = () => document.documentElement.dataset.pwa !== undefined

function supportsFileShare() {
  if (typeof navigator.share !== 'function' || !navigator.canShare) {
    return false
  }

  return navigator.canShare({
    files: [new File([''], 'a.pdf', { type: 'application/pdf' })],
  })
}

export default function useSchedulePdfShare() {
  const state = ref('idle')
  let file = null

  // Only an installed PWA needs this; browser tabs keep the plain link.
  function canHandle() {
    return isInstalledPwa() && supportsFileShare()
  }

  async function share() {
    try {
      await navigator.share({ files: [file] })
      state.value = 'idle'
      file = null
    } catch (error) {
      if (error?.name === 'AbortError') {
        state.value = 'idle'
        file = null
      } else if (error?.name === 'NotAllowedError') {
        state.value = 'ready'
      } else {
        state.value = 'failed'
      }
    }
  }

  async function start(url, filename) {
    state.value = 'loading'

    try {
      const response = await fetch(url)

      if (!response.ok) {
        throw new Error(`PDF request failed: ${response.status}`)
      }

      file = new File([await response.blob()], filename, {
        type: 'application/pdf',
      })
    } catch (error) {
      state.value = 'failed'

      return
    }

    await share()
  }

  return { state, canHandle, start, share }
}
