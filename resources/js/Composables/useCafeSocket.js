import { reactive, ref } from 'vue'

// The café mirrors the study room's socket: live seat state is fetched over
// REST and kept in sync over Echo, never through Inertia props. Every
// mutation awaits the server and replaces `state` with what it returned, so
// the client never decides who won a seat. A visitor without a schedule can
// watch (state + Echo are public) but `take` is refused by the server.
//
// A café seat is a study-room seat in another room, so this exposes what
// useStudyTimer needs of a socket (mySeat, allSeats, setState,
// resolveErrorMessage, leave, onStateChange) and the focus timer runs here
// unchanged. Returned reactive(), so callers read `socket.heldSeatCode`, not
// `.value`.
export default function useCafeSocket(config) {
  const state = ref(null)
  const loading = ref(true)
  const heldSeatCode = ref(null)
  const busySeatCode = ref(null)
  const errorMessage = ref(null)
  const realtime = ref(false)

  let channel = null
  let heartbeatHandle = null
  const seatEventCallbacks = []
  const noteEventCallbacks = []
  const stateChangeCallbacks = []

  function onSeatEvent(callback) {
    seatEventCallbacks.push(callback)
  }

  // The wall changed (a note was posted or taken down): the page re-reads it.
  function onNoteEvent(callback) {
    noteEventCallbacks.push(callback)
  }

  // Fires after the room state was replaced or patched, so the timer can
  // restart its tick and close focus mode when the timer was ended elsewhere.
  function onStateChange(callback) {
    stateChangeCallbacks.push(callback)
  }

  function emitStateChange() {
    for (const callback of stateChangeCallbacks) {
      callback()
    }
  }

  function emitSeatEvent(type, code, reason = null) {
    for (const callback of seatEventCallbacks) {
      callback({ type, code, reason })
    }
  }

  function seats() {
    return state.value ? state.value.seats : []
  }

  function setState(next) {
    state.value = next
    heldSeatCode.value = next.seats.find(seat => seat.isYou)?.code ?? null
    emitStateChange()
  }

  async function refresh() {
    try {
      const response = await window.axios.get('/cafe/state', {
        headers: state.value
          ? { 'If-None-Match': '"' + state.value.version + '"' }
          : {},
        validateStatus: status => status === 200 || status === 304,
      })

      if (response.status === 200) {
        setState(response.data)
      }
    } catch {
      // A failed poll keeps the last known state.
    }
  }

  async function load() {
    loading.value = true

    try {
      await refresh()
    } finally {
      loading.value = false
    }
  }

  function applyDelta(payload) {
    if (!state.value) {
      refresh()
      return
    }

    if (payload.type?.startsWith('note.')) {
      for (const callback of noteEventCallbacks) {
        callback(payload.type)
      }

      return
    }

    const target = seats().find(seat => seat.code === payload.seatCode)

    if (payload.seat && target) {
      const wasHeld = target.code === heldSeatCode.value

      if (wasHeld && !payload.seat.isOccupied) {
        heldSeatCode.value = null
        emitSeatEvent('released', target.code, payload.type)
      }

      Object.assign(target, payload.seat, {
        isYou: target.code === heldSeatCode.value,
      })
    }

    state.value.occupantCount = payload.occupantCount
    state.value.version = payload.version
    emitStateChange()
  }

  function connectRealtime() {
    if (channel || typeof window.Echo === 'undefined') {
      return
    }

    channel = window.Echo.channel('cafe')
    channel.listen('.cafe.updated', applyDelta)
    realtime.value = true
  }

  async function heartbeat() {
    try {
      const response = await window.axios.post('/cafe/heartbeat')

      if (!response.data.stillSeated && heldSeatCode.value) {
        const code = heldSeatCode.value
        heldSeatCode.value = null
        refresh()
        emitSeatEvent('released', code)
      }
    } catch {
      // The next beat tries again.
    }
  }

  function resolveErrorMessage(error) {
    return error.response?.data?.message || '網路好像不太穩定，請稍後再試一次。'
  }

  async function take(code) {
    if (busySeatCode.value || heldSeatCode.value) {
      return
    }

    busySeatCode.value = code

    try {
      const response = await window.axios.post('/cafe/seats/' + code + '/take')
      setState(response.data.state)
      errorMessage.value = null

      if (heldSeatCode.value === code) {
        emitSeatEvent('taken', code)
      }
    } catch (error) {
      errorMessage.value = resolveErrorMessage(error)
      refresh()
    } finally {
      busySeatCode.value = null
    }
  }

  async function leave() {
    const leftCode = heldSeatCode.value

    try {
      const response = await window.axios.post('/cafe/seat/leave')
      setState(response.data.state)

      if (leftCode) {
        emitSeatEvent('left', leftCode)
      }
    } catch (error) {
      errorMessage.value = resolveErrorMessage(error)
    }
  }

  function mySeat() {
    return seats().find(seat => seat.code === heldSeatCode.value) ?? null
  }

  function allSeats() {
    return seats()
  }

  function firstFreeSeat() {
    return seats().find(seat => !seat.isOccupied) ?? null
  }

  function start() {
    heartbeatHandle = setInterval(() => {
      if (heldSeatCode.value) {
        heartbeat()
      }
    }, config.heartbeatIntervalSeconds * 1000)

    if (window.__echoReady) {
      connectRealtime()
    }

    window.addEventListener('echoReady', connectRealtime)
  }

  function stop() {
    clearInterval(heartbeatHandle)
    window.removeEventListener('echoReady', connectRealtime)

    if (channel) {
      window.Echo.leaveChannel('cafe')
      channel = null
    }

    realtime.value = false
  }

  return reactive({
    state,
    loading,
    heldSeatCode,
    busySeatCode,
    errorMessage,
    realtime,
    seats,
    allSeats,
    mySeat,
    firstFreeSeat,
    setState,
    resolveErrorMessage,
    load,
    refresh,
    take,
    leave,
    heartbeat,
    start,
    stop,
    onSeatEvent,
    onNoteEvent,
    onStateChange,
  })
}
