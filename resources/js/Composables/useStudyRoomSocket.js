import { reactive, ref } from 'vue'

// Vue port of the "state sync" + "realtime" + "actions" sections of
// resources/js/study-room.js (the old Alpine `nouStudyRoom` factory).
//
// This is the ONE place StudyRoom deliberately breaks from the rest of the
// Inertia migration: per AGENTS.md "自習室 (Study Room)" and
// .github/skills/laravel-best-practices/rules/inertia-vue-views.md, the live
// seat/session state is fetched and mutated via the existing REST JSON
// endpoints and kept in sync over Echo/Reverb, never through Inertia props
// or router.reload(). `state` here is plain client state owned by this
// composable, not an Inertia page prop.
//
// Race safety: every mutation below (take/leave/timer actions) always
// AWAITS the server's response and only then calls setState(response.data.state)
// — there is no optimistic local mutation of seat occupancy anywhere in this
// file. The actual seat claim is an atomic conditional UPDATE on the server
// (see NouTools\Domains\StudyRoom\Actions\TakeSeat) — the client never
// decides who "won" a seat, it only ever reflects what the server decided.
export default function useStudyRoomSocket(config) {
  // Starts null — the page shell renders via Inertia without room state, and
  // this composable fetches it itself on mount (see load()).
  const state = ref(null)
  const loading = ref(true)
  const heldSeatCode = ref(null)
  const busySeatCode = ref(null)
  const errorMessage = ref(null)
  const realtime = ref(false)
  const connectionFailed = ref(false)

  let realtimeChannel = null
  let heartbeatHandle = null
  let connectTimeoutHandle = null
  const onStateChangeCallbacks = []

  function onStateChange(callback) {
    onStateChangeCallbacks.push(callback)
  }

  function deriveHeldSeatCode(roomState) {
    for (const floor of roomState.floors) {
      const mineSolo = floor.soloSeats.find(seat => seat.isYou)

      if (mineSolo) {
        return mineSolo.code
      }

      for (const table of floor.tables) {
        const mineTableSeat = table.seats.find(seat => seat.isYou)

        if (mineTableSeat) {
          return mineTableSeat.code
        }
      }
    }

    return null
  }

  function allSeats() {
    if (!state.value) {
      return []
    }

    const seats = []

    for (const floor of state.value.floors) {
      seats.push(...floor.soloSeats)

      for (const table of floor.tables) {
        seats.push(...table.seats)
      }
    }

    return seats
  }

  function floorForSeat(code) {
    if (!state.value) {
      return null
    }

    for (const floor of state.value.floors) {
      if (floor.soloSeats.some(seat => seat.code === code)) {
        return floor
      }

      for (const table of floor.tables) {
        if (table.seats.some(seat => seat.code === code)) {
          return floor
        }
      }
    }

    return null
  }

  function mySeat() {
    if (!heldSeatCode.value) {
      return null
    }

    return allSeats().find(seat => seat.code === heldSeatCode.value) || null
  }

  function setState(next) {
    state.value = next
    heldSeatCode.value = deriveHeldSeatCode(next)

    for (const callback of onStateChangeCallbacks) {
      callback(next)
    }
  }

  async function refresh() {
    try {
      const response = await window.axios.get('/study-room/state', {
        headers: state.value
          ? { 'If-None-Match': '"' + state.value.version + '"' }
          : {},
        validateStatus: status => status === 200 || status === 304,
      })

      if (response.status === 200) {
        setState(response.data)
      }
    } catch (error) {
      // Passive background refresh — degrade silently, the next realtime
      // event (or a manual retry) will catch the room back up.
    }
  }

  // The initial fetch, called once from the page's onMounted (StudyRoom
  // deliberately does NOT get this from an Inertia prop — see the file
  // banner above).
  async function load() {
    loading.value = true

    try {
      await refresh()
    } finally {
      loading.value = false
    }
  }

  function applyDelta(payload) {
    // A broadcast can arrive before the initial load() resolves — fetch the
    // full state instead of trying to patch a delta onto nothing.
    if (!state.value) {
      refresh()
      return
    }

    // A floor we don't have seat data for yet just opened — the delta only
    // carries one seat, not a whole new floor, so fall back to a full
    // refresh instead of trying to render a floor with no data.
    if (payload.openFloors > state.value.floors.length) {
      refresh()
      return
    }

    // A floor closed — drop it instead of leaving a stale floor rendered.
    if (payload.openFloors < state.value.floors.length) {
      state.value.floors = state.value.floors.slice(0, payload.openFloors)
    }

    state.value.openFloors = payload.openFloors
    // `payload.totals.yourFocusSecondsToday` is always 0: broadcasts fan out
    // from a single viewer-less build. Keep the value this browser already
    // has instead of stomping it.
    state.value.totals = {
      ...payload.totals,
      yourFocusSecondsToday: state.value.totals.yourFocusSecondsToday,
    }
    state.value.version = payload.version

    if (payload.seat) {
      patchSeat(payload.seat)
    }

    for (const callback of onStateChangeCallbacks) {
      callback(state.value)
    }
  }

  function patchSeat(incomingSeat) {
    const target = allSeats().find(seat => seat.code === incomingSeat.code)

    if (!target) {
      return
    }

    const wasOccupied = target.isOccupied
    const isHeldSeat = incomingSeat.code === heldSeatCode.value

    // The seat we hold was released out from under us (idle kick, admin
    // clear, etc.) — drop our local "this is mine" state immediately.
    if (isHeldSeat && !incomingSeat.isOccupied) {
      heldSeatCode.value = null
    }

    Object.assign(target, incomingSeat, {
      // Fan-out broadcasts always carry isYou: false — derive it ourselves
      // from the seat code we actually hold.
      isYou: incomingSeat.code === heldSeatCode.value,
    })

    // The broadcast payload doesn't carry per-floor occupied counts, so the
    // floor badge has to be kept in sync here too.
    if (target.isOccupied !== wasOccupied) {
      const floor = floorForSeat(target.code)

      if (floor) {
        floor.occupiedCount += target.isOccupied ? 1 : -1
      }
    }
  }

  function connectRealtime() {
    if (realtimeChannel || typeof window.Echo === 'undefined') {
      return
    }

    realtimeChannel = window.Echo.channel('study-room')
    realtimeChannel.listen('.study-room.updated', payload => {
      applyDelta(payload)
    })
    realtime.value = true
    connectionFailed.value = false

    const pusher = window.Echo.connector && window.Echo.connector.pusher

    if (pusher && pusher.connection && pusher.connection.bind) {
      pusher.connection.bind('state_change', states => {
        realtime.value = states.current === 'connected'

        if (realtime.value) {
          connectionFailed.value = false
        } else if (
          states.current === 'unavailable' ||
          states.current === 'failed'
        ) {
          connectionFailed.value = true
        }
      })
    }
  }

  function disconnectRealtime() {
    if (realtimeChannel) {
      window.Echo.leaveChannel('study-room')
      realtimeChannel = null
    }

    realtime.value = false
  }

  function startHeartbeatLoop() {
    heartbeatHandle = setInterval(() => {
      if (heldSeatCode.value) {
        heartbeat()
      }
    }, config.heartbeatIntervalSeconds * 1000)
  }

  function stopHeartbeatLoop() {
    if (heartbeatHandle) {
      clearInterval(heartbeatHandle)
      heartbeatHandle = null
    }
  }

  async function heartbeat() {
    try {
      const response = await window.axios.post('/study-room/heartbeat')

      if (!response.data.stillSeated) {
        heldSeatCode.value = null
        refresh()
      }
    } catch (error) {
      // Passive background call — never surface this as an error toast.
    }
  }

  function resolveErrorMessage(error) {
    return (
      (error.response && error.response.data && error.response.data.message) ||
      '網路好像不太穩定，請稍後再試一次。'
    )
  }

  // --- seat actions: always await the server, then reconcile from its
  // response — never mutate occupancy locally before it confirms. ---

  async function take(code) {
    if (busySeatCode.value || heldSeatCode.value) {
      return
    }

    busySeatCode.value = code

    try {
      const response = await window.axios.post(
        '/study-room/seats/' + code + '/take'
      )
      setState(response.data.state)
      errorMessage.value = null
    } catch (error) {
      errorMessage.value = resolveErrorMessage(error)
    } finally {
      busySeatCode.value = null
    }
  }

  async function leave() {
    const response = await window.axios.post('/study-room/seat/leave')
    setState(response.data.state)
    return response
  }

  function start() {
    startHeartbeatLoop()

    if (window.__echoReady) {
      connectRealtime()
    }

    window.addEventListener('echoReady', connectRealtime)

    connectTimeoutHandle = setTimeout(() => {
      if (!realtime.value) {
        connectionFailed.value = true
      }
    }, config.realtimeConnectTimeoutSeconds * 1000)
  }

  function stop() {
    stopHeartbeatLoop()
    disconnectRealtime()
    window.removeEventListener('echoReady', connectRealtime)

    if (connectTimeoutHandle) {
      clearTimeout(connectTimeoutHandle)
      connectTimeoutHandle = null
    }
  }

  return reactive({
    state,
    loading,
    load,
    heldSeatCode,
    busySeatCode,
    errorMessage,
    realtime,
    connectionFailed,
    allSeats,
    floorForSeat,
    mySeat,
    setState,
    refresh,
    applyDelta,
    onStateChange,
    take,
    leave,
    heartbeat,
    resolveErrorMessage,
    start,
    stop,
  })
}
