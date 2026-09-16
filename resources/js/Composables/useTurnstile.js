import { ref } from 'vue'

// All three forms in the discount-stores/ domain need the exact same "load
// the script once, render an explicit widget into a container, track
// whether the challenge has been solved" sequence, so it's extracted here
// rather than duplicated three times.
const SCRIPT_ID = 'cf-turnstile-script'
const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js'

let scriptPromise = null

function loadTurnstileScript() {
  if (window.turnstile) {
    return Promise.resolve()
  }

  if (!scriptPromise) {
    scriptPromise = new Promise(resolve => {
      const existing = document.getElementById(SCRIPT_ID)

      if (existing) {
        existing.addEventListener('load', () => resolve(), { once: true })
        return
      }

      const script = document.createElement('script')
      script.id = SCRIPT_ID
      script.src = SCRIPT_SRC
      script.defer = true
      script.addEventListener('load', () => resolve(), { once: true })
      document.head.appendChild(script)
    })
  }

  return scriptPromise
}

export default function useTurnstile() {
  const container = ref(null)
  const challengeExecuted = ref(false)
  let widgetId = null

  async function render(sitekey, options = {}) {
    await loadTurnstileScript()

    if (!container.value || widgetId !== null) {
      return
    }

    widgetId = window.turnstile.render(container.value, {
      sitekey,
      theme: options.theme ?? 'auto',
      language: options.language ?? 'en-US',
      size: options.size ?? 'normal',
      callback: token => {
        challengeExecuted.value = true
        options.onSuccess?.(token)
      },
      'error-callback': () => {
        challengeExecuted.value = false
        options.onInvalid?.()
      },
      'expired-callback': () => {
        challengeExecuted.value = false
        options.onInvalid?.()
      },
    })
  }

  function remove() {
    if (window.turnstile && widgetId !== null) {
      window.turnstile.remove(widgetId)
    }

    widgetId = null
    challengeExecuted.value = false
  }

  return {
    container,
    challengeExecuted,
    render,
    remove,
  }
}
