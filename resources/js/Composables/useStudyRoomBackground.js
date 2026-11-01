import { reactive } from 'vue'
import { playTimerFinishedSound } from '../study-room-sound'

// What the study room (or the café, whose seats are study-room seats in
// another room) hands to the shell when its page unmounts while the viewer
// still holds a seat: the seat's timer columns (for the pill's
// readout) and a heartbeat that keeps the seat alive, since the page's own
// socket stops with it. There is no Echo here: the viewer's own timer only
// changes through their own actions, so a refresh when the tab becomes
// visible again is enough.
//
// The hint in localStorage lets a full page load elsewhere pick the seat back
// up. It is only a hint: the room's state endpoint stays the authority, and a
// missing seat there clears it. `room` says which one to ask.
const HINT_KEY = 'nou:study-room:seated:v1'
const DEFAULT_INTERVAL_SECONDS = 60

const ROOM_URLS = {
  study: { state: '/study-room/state', heartbeat: '/study-room/heartbeat' },
  cafe: { state: '/cafe/state', heartbeat: '/cafe/heartbeat' },
}

const state = reactive({ seat: null, playSound: false, room: 'study' })

// Bumped by stop(), so a request that was in flight when the room page took
// over doesn't resurrect the shell's seat afterwards.
let generation = 0
let heartbeatHandle = null
let lastNotifiedTimerEndsAt = null

function readHint() {
  try {
    return JSON.parse(window.localStorage.getItem(HINT_KEY))
  } catch {
    return null
  }
}

function writeHint(hint) {
  try {
    window.localStorage.setItem(HINT_KEY, JSON.stringify(hint))
  } catch {
    // Blocked storage: a reload elsewhere just won't resume the pill.
  }
}

function removeHint() {
  try {
    window.localStorage.removeItem(HINT_KEY)
  } catch {
    // Nothing to remove.
  }
}

function findMySeat(roomState) {
  // The café's state is a flat list of seats.
  if (roomState.seats) {
    return roomState.seats.find(seat => seat.isYou) ?? null
  }

  for (const floor of roomState.floors ?? []) {
    const solo = floor.soloSeats.find(seat => seat.isYou)

    if (solo) {
      return solo
    }

    for (const table of floor.tables) {
      const seat = table.seats.find(candidate => candidate.isYou)

      if (seat) {
        return seat
      }
    }
  }

  return null
}

function timerFields(seat) {
  return {
    code: seat.code,
    timerMode: seat.timerMode,
    timerPhase: seat.timerPhase,
    timerEndsAt: seat.timerEndsAt,
    timerStartedAt: seat.timerStartedAt,
    pausedAt: seat.pausedAt,
  }
}

// A finish that happened while nobody was looking is not chimed afterwards.
function suppressAlreadyFinishedSound() {
  const seat = state.seat

  if (seat?.timerEndsAt && Date.parse(seat.timerEndsAt) <= Date.now()) {
    lastNotifiedTimerEndsAt = seat.timerEndsAt
  }
}

async function heartbeat() {
  try {
    const response = await window.axios.post(ROOM_URLS[state.room].heartbeat)

    if (!response.data.stillSeated) {
      clear()
    }
  } catch {
    // Passive background call, like the page's own heartbeat.
  }
}

async function refresh() {
  const startedIn = generation

  try {
    const response = await window.axios.get(ROOM_URLS[state.room].state)

    if (startedIn !== generation) {
      return
    }

    const seat = findMySeat(response.data)

    if (!seat) {
      clear()

      return
    }

    state.seat = timerFields(seat)
    suppressAlreadyFinishedSound()
  } catch {
    // Keep what we have; the next heartbeat or visit catches up.
  }
}

function handleVisibilityChange() {
  if (!document.hidden && state.seat) {
    refresh()
    heartbeat()
  }
}

function startHeartbeat(intervalSeconds) {
  stopHeartbeat()
  heartbeatHandle = setInterval(heartbeat, intervalSeconds * 1000)
  document.addEventListener('visibilitychange', handleVisibilityChange)
}

function stopHeartbeat() {
  if (heartbeatHandle) {
    clearInterval(heartbeatHandle)
    heartbeatHandle = null
  }

  document.removeEventListener('visibilitychange', handleVisibilityChange)
}

// The page is back (or going away without a seat): its own socket takes over.
function stop() {
  generation++
  stopHeartbeat()
  state.seat = null
}

function clear() {
  stop()
  removeHint()
}

// The seat the shell is carrying belongs to one room. A page of the other
// room (the café while a study seat is carried, or the reverse) must leave it
// alone: stopping its heartbeat would let the seat idle out.
function ownedBy(room) {
  const owner = state.seat ? state.room : (readHint()?.room ?? room)

  return owner === room
}

function stopFor(room) {
  if (ownedBy(room)) {
    stop()
  }
}

function clearFor(room) {
  if (ownedBy(room)) {
    clear()
  }
}

// Called by the study room or café page when it unmounts while holding a seat.
function handOff(
  seat,
  { playSound, heartbeatIntervalSeconds, room = 'study' }
) {
  const interval = heartbeatIntervalSeconds || DEFAULT_INTERVAL_SECONDS

  state.room = room
  state.seat = timerFields(seat)
  state.playSound = Boolean(playSound)
  suppressAlreadyFinishedSound()
  writeHint({ interval, playSound: state.playSound, room })
  startHeartbeat(interval)
}

// A full page load on another page: resume only if this browser last left the
// room holding a seat.
async function bootstrap() {
  const hint = readHint()

  if (state.seat || !hint) {
    return
  }

  const startedIn = generation

  state.playSound = Boolean(hint.playSound)
  state.room = hint.room === 'cafe' ? 'cafe' : 'study'
  await refresh()

  if (state.seat && startedIn === generation) {
    startHeartbeat(hint.interval || DEFAULT_INTERVAL_SECONDS)
  }
}

// Called from the pill's one-second tick.
function checkTimerEnd(nowMs) {
  const seat = state.seat

  if (
    !state.playSound ||
    !seat?.timerEndsAt ||
    seat.pausedAt ||
    Date.parse(seat.timerEndsAt) > nowMs ||
    lastNotifiedTimerEndsAt === seat.timerEndsAt
  ) {
    return
  }

  lastNotifiedTimerEndsAt = seat.timerEndsAt

  if (!document.hidden) {
    playTimerFinishedSound()
  }
}

export default function useStudyRoomBackground() {
  return {
    state,
    handOff,
    stop,
    clear,
    stopFor,
    clearFor,
    bootstrap,
    checkTimerEnd,
  }
}
