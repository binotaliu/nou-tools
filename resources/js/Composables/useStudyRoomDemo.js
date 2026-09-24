import { reactive, ref } from 'vue'

// A socket-shaped stand-in for visitors without a schedule, so the real floor
// markup, useSeatGrid and useStudyTimer render a preview of the room without
// touching GET /study-room/state, Echo or the heartbeat. Everything here is
// local fixture data: the occupants are fictional and never leave the
// browser. Only the surface those consumers read is implemented; every
// mutation is a no-op except take(), which raises the sign-up prompt.
const OCCUPANTS = [
  { nickname: '小浣熊', emoji: '🦝', activity: '讀 統計學', minutes: 18 },
  { nickname: '阿榕', emoji: '🌱', activity: '寫 心理學作業', minutes: 12 },
  { nickname: '夜貓', emoji: '🐱', activity: '複習 民法概要', minutes: 21 },
  {
    nickname: '小滿',
    emoji: '🍵',
    activity: '讀 英文',
    minutes: 9,
    phase: 'break',
  },
  {
    nickname: '海苔',
    emoji: '🐙',
    activity: '準備 期中考',
    minutes: 14,
    paused: true,
  },
  { nickname: '阿哲', emoji: '📚', activity: '讀 經濟學', minutes: 24 },
  { nickname: '柚子', emoji: '🍊', activity: '寫 報告', minutes: 16 },
  { nickname: '大雄', emoji: '🐢', activity: '讀 管理學', minutes: 11 },
]

// Every third seat is taken, offset per seat so tables and the solo row
// don't fill in a visibly regular stripe.
function isDemoOccupied(index) {
  return index % 3 === 1 || index % 7 === 0
}

export default function useStudyRoomDemo(config) {
  const state = ref(null)
  const promptOpen = ref(false)
  const onStateChangeCallbacks = []

  let occupiedCount = 0

  function buildSeat(code, groupCode, seatNumber, label, index, mountedAt) {
    const occupant = isDemoOccupied(index)
      ? OCCUPANTS[occupiedCount++ % OCCUPANTS.length]
      : null
    const onBreak = occupant?.phase === 'break'
    const endsAt = occupant
      ? new Date(mountedAt + occupant.minutes * 60 * 1000).toISOString()
      : null

    return {
      code,
      kind: groupCode ? 'shared' : 'solo',
      groupCode,
      seatNumber,
      label,
      isOccupied: occupant !== null,
      isYou: false,
      nickname: occupant?.nickname ?? null,
      emoji: occupant?.emoji ?? null,
      activity: onBreak ? null : (occupant?.activity ?? null),
      activityVerb: null,
      subjectCourseId: null,
      timerMode: occupant ? 'pomodoro' : null,
      timerPhase: occupant ? (onBreak ? 'break' : 'focus') : null,
      timerRound: occupant ? 1 : null,
      roundsPerCycle: occupant ? config.timerPomodoroRoundsPerCycle : null,
      timerEndsAt: endsAt,
      timerStartedAt: occupant
        ? new Date(mountedAt - 5 * 60 * 1000).toISOString()
        : null,
      pausedAt: occupant?.paused ? new Date(mountedAt).toISOString() : null,
    }
  }

  function buildState() {
    const mountedAt = Date.now()
    let index = 0

    const soloSeats = []

    for (let n = 1; n <= config.soloSeatsPerFloor; n++) {
      const code = '1-S' + String(n).padStart(2, '0')

      soloSeats.push(
        buildSeat(
          code,
          null,
          n,
          '1F 單人座 ' + String(n).padStart(2, '0'),
          index++,
          mountedAt
        )
      )
    }

    const tables = []

    for (let t = 1; t <= config.tablesPerFloor; t++) {
      const seats = []

      for (let n = 1; n <= config.seatsPerTable; n++) {
        seats.push(
          buildSeat(
            '1-T' + t + '-' + n,
            'T' + t,
            n,
            '1F ' + t + ' 號桌 ' + n + ' 位',
            index++,
            mountedAt
          )
        )
      }

      tables.push({ groupCode: 'T' + t, label: t + ' 號桌', seats })
    }

    const all = [...soloSeats, ...tables.flatMap(table => table.seats)]

    return {
      floors: [
        {
          floor: 1,
          label: '一樓',
          soloSeats,
          tables,
          occupiedCount: all.filter(seat => seat.isOccupied).length,
          totalCount: all.length,
        },
      ],
      openFloors: 1,
      totals: { siteFocusSecondsToday: 0, yourFocusSecondsToday: 0 },
      serverTime: new Date(mountedAt).toISOString(),
      version: 'demo',
    }
  }

  function onStateChange(callback) {
    onStateChangeCallbacks.push(callback)
  }

  // Builds the fixture on mount rather than at setup so the countdowns are
  // measured from when the visitor arrives, then tells the timer to start
  // ticking, as a real state fetch would.
  async function load() {
    state.value = buildState()
    onStateChangeCallbacks.forEach(callback => callback())
  }

  function allSeats() {
    if (!state.value) {
      return []
    }

    return state.value.floors.flatMap(floor => [
      ...floor.soloSeats,
      ...floor.tables.flatMap(table => table.seats),
    ])
  }

  function floorForSeat(code) {
    return (
      state.value?.floors.find(floor =>
        [
          ...floor.soloSeats,
          ...floor.tables.flatMap(table => table.seats),
        ].some(seat => seat.code === code)
      ) ?? null
    )
  }

  function take() {
    promptOpen.value = true
  }

  function noop() {}

  return reactive({
    demo: true,
    state,
    loading: false,
    load,
    promptOpen,
    heldSeatCode: null,
    busySeatCode: null,
    errorMessage: null,
    realtime: false,
    connectionFailed: false,
    allSeats,
    floorForSeat,
    mySeat: () => null,
    setState: noop,
    refresh: noop,
    applyDelta: noop,
    onStateChange,
    take,
    leave: noop,
    heartbeat: noop,
    resolveErrorMessage: () => null,
    start: noop,
    stop: noop,
  })
}
