// Alpine factory for the 自習室 (study room) page root. All interactive
// logic lives here rather than in x-* attributes: the project ships
// @alpinejs/csp, whose expression evaluator rejects arrow functions,
// template literals, and bare global references inside directives, so
// every string built for the template (labels, classes, testids,
// countdowns) is built here in plain JS and only *read* from attributes.
//
// Realtime-only, no polling fallback: resources/js/echo.js only builds
// window.Echo when VITE_REVERB_APP_KEY is set at build time, and even then
// the Reverb connection itself can fail. Either way, if a connection isn't
// confirmed live within `realtimeConnectTimeoutSeconds`, this component
// gives up and surfaces `connectionFailed` instead of retrying forever.
export default function nouStudyRoom(initial) {
  return {
    state: initial.roomState,
    config: initial.clientConfig,
    subjects: initial.subjects,
    verbs: initial.verbs,
    hasSchedule: initial.hasSchedule,
    needsProfile: initial.needsProfile,

    profileNickname: initial.profile.nickname,
    profileEmoji: initial.profile.emoji,
    canChangeNickname: initial.profile.canChangeNickname,
    canChangeNicknameAt: initial.profile.canChangeNicknameAt,
    emojiChoices: initial.emojiChoices,

    personalInfoOpen: false,
    sessionsLoading: false,
    sessionsFetched: false,
    recentSessions: [],

    now: Date.now(),
    clockNow: Date.now(),
    heldSeatCode: null,
    busySeatCode: null,
    panelBusy: false,
    errorMessage: null,
    realtime: false,
    connectionFailed: false,

    timerMode: 'pomodoro',
    customMinutes: 25,
    selectedVerb: null,
    selectedSubjectCourseId: null,

    tickHandle: null,
    heartbeatHandle: null,
    connectTimeoutHandle: null,
    clockHandle: null,
    realtimeChannel: null,

    init() {
      this.heldSeatCode = this.deriveHeldSeatCode(this.state)
      this.customMinutes = this.config.timerCustomMinMinutes
      this.selectedVerb = this.verbs.length ? this.verbs[0].value : null
      // Kept as a string (matching the <select> option values, including
      // '' for the 其他 sentinel) rather than a number, so x-model doesn't
      // have to juggle two different JS types before/after a user pick —
      // it's only converted back to number|null right before it's sent.
      this.selectedSubjectCourseId =
        this.subjects.length && this.subjects[0].id !== null
          ? String(this.subjects[0].id)
          : ''

      if (this.needsProfile) {
        this.personalInfoOpen = true
      }

      this.clockHandle = setInterval(() => {
        this.clockNow = Date.now()
      }, 1000)

      this.restartTickIfNeeded()
      this.startHeartbeatLoop()

      if (window.__echoReady) {
        this.connectRealtime()
      }

      window.addEventListener('echoReady', () => this.connectRealtime())

      this.connectTimeoutHandle = setTimeout(() => {
        if (!this.realtime) {
          this.connectionFailed = true
        }
      }, this.config.realtimeConnectTimeoutSeconds * 1000)

      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
          this.refresh()
          this.heartbeat()
        }
      })
    },

    // --- realtime -----------------------------------------------------

    connectRealtime() {
      if (this.realtimeChannel || typeof window.Echo === 'undefined') {
        return
      }

      this.realtimeChannel = window.Echo.channel('study-room')
      this.realtimeChannel.listen('.study-room.updated', payload => {
        this.applyDelta(payload)
      })
      this.realtime = true
      this.connectionFailed = false

      const pusher = window.Echo.connector && window.Echo.connector.pusher

      if (pusher && pusher.connection && pusher.connection.bind) {
        pusher.connection.bind('state_change', states => {
          this.realtime = states.current === 'connected'

          if (this.realtime) {
            this.connectionFailed = false
          } else if (
            states.current === 'unavailable' ||
            states.current === 'failed'
          ) {
            this.connectionFailed = true
          }
        })
      }
    },

    // --- heartbeat / tick ------------------------------------------------

    startHeartbeatLoop() {
      this.heartbeatHandle = setInterval(() => {
        if (this.heldSeatCode) {
          this.heartbeat()
        }
      }, this.config.heartbeatIntervalSeconds * 1000)
    },

    // Only ticks while a countdown is actually visible somewhere on the
    // page, so an idle/empty room never keeps a per-second timer alive.
    restartTickIfNeeded() {
      if (this.tickHandle || !this.hasVisibleCountdown()) {
        return
      }

      this.tickHandle = setInterval(() => {
        this.now = Date.now()

        if (!this.hasVisibleCountdown()) {
          clearInterval(this.tickHandle)
          this.tickHandle = null
        }
      }, 1000)
    },

    hasVisibleCountdown() {
      return this.allSeats().some(
        seat => seat.timerEndsAt && Date.parse(seat.timerEndsAt) > Date.now()
      )
    },

    // --- state sync -----------------------------------------------------

    allSeats() {
      const seats = []

      for (const floor of this.state.floors) {
        seats.push(...floor.soloSeats)

        for (const table of floor.tables) {
          seats.push(...table.seats)
        }
      }

      return seats
    },

    deriveHeldSeatCode(state) {
      for (const floor of state.floors) {
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
    },

    setState(state) {
      this.state = state
      this.heldSeatCode = this.deriveHeldSeatCode(state)
      this.restartTickIfNeeded()
    },

    async refresh() {
      try {
        const response = await window.axios.get('/study-room/state', {
          headers: { 'If-None-Match': '"' + this.state.version + '"' },
          validateStatus: status => status === 200 || status === 304,
        })

        if (response.status === 200) {
          this.setState(response.data)
        }
      } catch (error) {
        // Passive background refresh — degrade silently, the next realtime
        // event (or a manual retry) will catch the room back up.
      }
    },

    applyDelta(payload) {
      // A floor we don't have seat data for yet just opened — the delta
      // only carries one seat, not a whole new floor, so fall back to a
      // full refresh instead of trying to render a floor with no data.
      if (payload.openFloors > this.state.floors.length) {
        this.refresh()
        return
      }

      this.state.openFloors = payload.openFloors
      this.state.totals = payload.totals
      this.state.version = payload.version

      if (payload.seat) {
        this.patchSeat(payload.seat)
      }

      this.restartTickIfNeeded()
    },

    patchSeat(incomingSeat) {
      const target = this.allSeats().find(
        seat => seat.code === incomingSeat.code
      )

      if (!target) {
        return
      }

      Object.assign(target, incomingSeat, {
        // Fan-out broadcasts always carry isYou: false — derive it
        // ourselves from the seat code we actually hold.
        isYou: incomingSeat.code === this.heldSeatCode,
      })
    },

    // --- actions --------------------------------------------------------

    async take(code) {
      if (this.busySeatCode) {
        return
      }

      this.busySeatCode = code

      try {
        const response = await window.axios.post(
          '/study-room/seats/' + code + '/take'
        )
        this.setState(response.data.state)
        this.errorMessage = null
      } catch (error) {
        this.errorMessage = this.resolveErrorMessage(error)
      } finally {
        this.busySeatCode = null
      }
    },

    async leave() {
      if (this.panelBusy) {
        return
      }

      this.panelBusy = true

      try {
        const response = await window.axios.post('/study-room/seat/leave')
        this.setState(response.data.state)
        this.errorMessage = null
      } catch (error) {
        this.errorMessage = this.resolveErrorMessage(error)
      } finally {
        this.panelBusy = false
      }
    },

    async startTimer() {
      if (this.panelBusy) {
        return
      }

      this.panelBusy = true

      try {
        const response = await window.axios.post('/study-room/timer', {
          mode: this.timerMode,
          minutes: this.timerMode === 'custom' ? this.customMinutes : null,
          verb: this.selectedVerb,
          subjectCourseId:
            this.selectedSubjectCourseId === ''
              ? null
              : Number(this.selectedSubjectCourseId),
        })
        this.setState(response.data.state)
        this.errorMessage = null
      } catch (error) {
        this.errorMessage = this.resolveErrorMessage(error)
      } finally {
        this.panelBusy = false
      }
    },

    async stopTimer() {
      if (this.panelBusy) {
        return
      }

      this.panelBusy = true

      try {
        const response = await window.axios.delete('/study-room/timer')
        this.setState(response.data.state)
        this.errorMessage = null
      } catch (error) {
        this.errorMessage = this.resolveErrorMessage(error)
      } finally {
        this.panelBusy = false
      }
    },

    async startBreak() {
      if (this.panelBusy) {
        return
      }

      this.panelBusy = true

      try {
        const response = await window.axios.post('/study-room/timer/break')
        this.setState(response.data.state)
        this.errorMessage = null
      } catch (error) {
        this.errorMessage = this.resolveErrorMessage(error)
      } finally {
        this.panelBusy = false
      }
    },

    async heartbeat() {
      try {
        const response = await window.axios.post('/study-room/heartbeat')

        if (!response.data.stillSeated) {
          this.heldSeatCode = null
          this.refresh()
        }
      } catch (error) {
        // Passive background call — never surface this as an error toast.
      }
    },

    resolveErrorMessage(error) {
      return (
        (error.response &&
          error.response.data &&
          error.response.data.message) ||
        '網路好像不太穩定，請稍後再試一次。'
      )
    },

    // --- personal info modal ---------------------------------------------

    openPersonalInfo() {
      this.personalInfoOpen = true
      this.loadRecentSessions()
    },

    async loadRecentSessions() {
      if (this.sessionsFetched || this.sessionsLoading) {
        return
      }

      this.sessionsLoading = true

      try {
        const response = await window.axios.get('/study-room/sessions')
        this.recentSessions = response.data.sessions
        this.sessionsFetched = true
      } catch (error) {
        // Passive — the log is a nice-to-have inside the modal, never
        // blocks the rest of it from working.
      } finally {
        this.sessionsLoading = false
      }
    },

    sessionDurationLabel(session) {
      return this.formatDurationLabel(session.focusSeconds)
    },

    nicknameCooldownLabel() {
      if (this.canChangeNickname || !this.canChangeNicknameAt) {
        return ''
      }

      const days = Math.ceil(
        (Date.parse(this.canChangeNicknameAt) - this.clockNow) / 86400000
      )

      return days > 0 ? '可於 ' + days + ' 天後修改' : '可於今天內修改'
    },

    // --- clock --------------------------------------------------------

    clockTimeLabel() {
      const { hour, minute } = window.NouTime.taipeiHM(new Date(this.clockNow))

      return hour + ':' + minute
    },

    clockDateLabel() {
      const ymd = window.NouTime.taipeiYmd(new Date(this.clockNow))

      return (
        window.NouTime.monthDay(ymd) +
        ' 週' +
        window.NouTime.weekdayFromYmd(ymd)
      )
    },

    // --- display helpers --------------------------------------------------

    pad2(n) {
      return String(n).padStart(2, '0')
    },

    remainingLabel(seat) {
      if (!seat.timerEndsAt) {
        return ''
      }

      const totalSeconds = Math.max(
        0,
        Math.ceil((Date.parse(seat.timerEndsAt) - this.now) / 1000)
      )
      const minutes = Math.floor(totalSeconds / 60)
      const seconds = totalSeconds % 60

      return this.pad2(minutes) + ':' + this.pad2(seconds)
    },

    isSeatFinishedFocus(seat) {
      return (
        seat.timerPhase === 'focus' &&
        seat.timerEndsAt !== null &&
        Date.parse(seat.timerEndsAt) <= this.now
      )
    },

    // Timer line shown above the seat's emoji: the mm:ss countdown while
    // one is running, otherwise a placeholder so every occupied seat keeps
    // the same 3-line layout.
    timerLabel(seat) {
      if (!seat.timerEndsAt) {
        return '--:--'
      }

      return this.remainingLabel(seat)
    },

    // Thought-bubble text floating above the timer: what the occupant is
    // doing, with no duration in it (the timer line already covers that).
    thoughtBubbleText(seat) {
      if (!seat.timerMode) {
        return '剛坐下，還沒開始計時'
      }

      if (seat.timerPhase === 'break') {
        return '休息中'
      }

      if (this.isSeatFinishedFocus(seat)) {
        return (seat.activity || '專注') + '（已完成，準備休息）'
      }

      return seat.activity || '專注中'
    },

    // Heuristic, not a DOM measurement: the bubble is a fixed, narrow box
    // and this project's Alpine build is CSP-locked (no arrow functions or
    // template literals in directives), so per-seat scrollWidth checks
    // aren't practical inside an x-for loop. A character-count cutoff
    // tuned to the bubble's width is good enough to decide when text needs
    // to marquee instead of just being clipped.
    needsMarquee(seat) {
      const text = this.thoughtBubbleText(seat)

      return !!text && text.length > 8
    },

    seatTestId(seat) {
      return 'seat-' + seat.code
    },

    isMine(seat) {
      return seat.code === this.heldSeatCode
    },

    seatAriaLabel(seat) {
      if (!seat.isOccupied) {
        return seat.label + '，空位，點擊入座'
      }

      return seat.label + '，' + (seat.nickname || '同學') + ' 正在使用'
    },

    seatClasses(seat) {
      const classes = [
        'relative flex flex-col items-center justify-center gap-0.5 rounded-lg border p-2 text-center transition min-h-16',
      ]

      if (this.isMine(seat)) {
        classes.push(
          'border-orange-500 bg-orange-50 ring-2 ring-orange-400 dark:bg-orange-950/30 dark:ring-orange-500'
        )
      } else if (seat.isOccupied) {
        classes.push(
          'border-warm-300 bg-warm-100 dark:border-zinc-700 dark:bg-zinc-800'
        )
      } else {
        classes.push(
          'border-dashed border-warm-300 bg-white text-warm-500 hover:border-warm-500 hover:bg-warm-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800'
        )
      }

      if (this.busySeatCode === seat.code) {
        classes.push('opacity-50 cursor-wait')
      }

      return classes.join(' ')
    },

    tableSeatPositionClass(index) {
      // Arranges a table's 4 seats N/E/S/W around a centered tabletop,
      // so a shared table actually reads as a table with people around
      // it rather than another grid of squares.
      const positions = [
        'col-start-2 row-start-1',
        'col-start-3 row-start-2',
        'col-start-2 row-start-3',
        'col-start-1 row-start-2',
      ]

      return positions[index % positions.length]
    },

    mySeat() {
      if (!this.heldSeatCode) {
        return null
      }

      return (
        this.allSeats().find(seat => seat.code === this.heldSeatCode) || null
      )
    },

    canStartBreak() {
      const seat = this.mySeat()

      return !!seat && this.isSeatFinishedFocus(seat)
    },

    hasRunningTimer() {
      const seat = this.mySeat()

      return !!seat && !!seat.timerMode && !this.isSeatFinishedFocus(seat)
    },

    formatDurationLabel(totalSeconds) {
      const hours = Math.floor(totalSeconds / 3600)
      const minutes = Math.floor((totalSeconds % 3600) / 60)

      return hours > 0 ? hours + ' 小時 ' + minutes + ' 分' : minutes + ' 分'
    },

    focusTotalLabel() {
      return this.formatDurationLabel(this.state.totals.siteFocusSecondsToday)
    },

    yourFocusTotalLabel() {
      return this.formatDurationLabel(this.state.totals.yourFocusSecondsToday)
    },
  }
}
