// Separate Vite entry (mirrors leaflet.js) so pages that don't need realtime
// don't pay for pusher-js/laravel-echo. `window.Echo` is only constructed
// when VITE_REVERB_APP_KEY is set at build time; without it (CI, tests, or a
// deploy without Reverb configured) this file is a silent no-op and pages
// must fall back to polling. Listeners should wait for the `echoReady`
// window event, or check `window.__echoReady` if they subscribe late.
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const key = import.meta.env.VITE_REVERB_APP_KEY

if (typeof key === 'string' && key.length > 0) {
  window.Pusher = Pusher

  const scheme = import.meta.env.VITE_REVERB_SCHEME

  window.Echo = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
  })

  window.__echoReady = true

  // Dispatch on the next macrotask so a listener registered later in the
  // same tick (e.g. from a component's onMounted) still catches the event.
  setTimeout(() => {
    window.dispatchEvent(new CustomEvent('echoReady'))
  }, 0)
}
