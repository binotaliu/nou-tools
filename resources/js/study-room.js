import { buildStarField, computeSky, SKY_PHASE_LABELS } from './study-room-sky'
import { createSkyRenderer } from './study-room-sky-shader'
import { playTimerFinishedSound } from './study-room-sound'

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
// `?sky-at=<ISO 8601>` freezes the windows/garden at that instant (the
// clock itself keeps running) — handy for eyeballing dusk or a full moon
// without waiting for one. Ignored unless it parses.
function readSkyOverrideFromUrl() {
  const raw = new URLSearchParams(window.location.search).get('sky-at')

  if (!raw) {
    return null
  }

  // URLSearchParams decodes a literal '+' in the query string as a space
  // (the application/x-www-form-urlencoded convention), so an unencoded
  // offset like '...T18:40:00+08:00' comes back as '...T18:40:00 08:00'.
  // Restore it so plain, copy-pasted ISO strings work without the caller
  // having to remember to percent-encode the '+' as %2B.
  const normalized = raw.replace(/ (\d{2}:\d{2})$/, '+$1')
  const ms = Date.parse(normalized)

  return Number.isNaN(ms) ? null : ms
}

// Where the sky's bodies are drawn in each place the sky appears, as
// fractions of that canvas's height from the top edge: `zenith` for a body
// straight overhead, `horizon` for one resting on the skyline. `shader` is
// the matching geometry handed to the WebGL renderer (which places the
// horizon a touch lower than the discs do, so the glow sits behind the
// skyline rather than on top of it).
const SKY_LAYOUTS = {
  // The garden strip above the ground floor.
  garden: {
    zenith: 0.06,
    horizon: 0.4,
    shader: { horizonY: 0.42, zenithY: 0.02 },
  },
  // The window in the focus view: a little garden fills its bottom, so
  // bodies come to rest just above it.
  focus: {
    zenith: 0.1,
    horizon: 0.7,
    shader: { horizonY: 0.72, zenithY: 0.05 },
  },
}

function clamp01(value) {
  return Math.min(1, Math.max(0, value))
}

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

    playSoundOnTimerEnd: initial.profile.playSoundOnTimerEnd,
    // The timerEndsAt already notified for, so overtime a seat was already
    // in in when the page loaded doesn't chime immediately, and a chime
    // doesn't repeat every tick while a finished timer sits in overtime.
    lastNotifiedTimerEndsAt: null,

    personalInfoOpen: false,
    sessionsLoading: false,
    sessionsFetched: false,
    recentSessions: [],

    now: Date.now(),
    clockNow: Date.now(),
    // Sun/moon/sky snapshot the windows and garden are drawn from. Only
    // replaced when the minute changes (see refreshSky) so the many
    // :style bindings reading it don't re-run on every clock tick.
    sky: computeSky(
      Date.now(),
      initial.clientConfig.latitude,
      initial.clientConfig.longitude
    ),
    skyMinute: null,
    skyOverrideMs: readSkyOverrideFromUrl(),
    skyStars: buildStarField(),
    // A denser field for the fullscreen focus view, where the sky is the
    // whole screen rather than a strip.
    focusStars: buildStarField(90),
    // WebGL renderers for the garden sky and the fullscreen focus sky, or
    // null when the browser can't give us a context — in which case the
    // CSS gradient underneath is what everyone sees, and the matching
    // *CanvasActive flag keeps the CSS sun haze that goes with it on
    // screen. The garden pair keeps its unqualified name because browser
    // tests reach in for it.
    skyRenderer: null,
    skyCanvasActive: false,
    focusSkyRenderer: null,
    focusSkyCanvasActive: false,
    // Fullscreen focus mode: the action banner taken over the whole page.
    focusMode: false,
    heldSeatCode: null,
    busySeatCode: null,
    // Table seat whose hover/tap popover is open.
    peekSeatCode: null,
    panelBusy: false,
    errorMessage: null,
    realtime: false,
    connectionFailed: false,

    timerMode: 'pomodoro',
    customMinutes: 25,
    selectedVerb: null,
    selectedSubjectCourseId: null,
    // The student's own pomodoro cycle, prefilled from their profile and
    // sent along with every pomodoro start so the server saves it.
    cycle: {
      focusMinutes: initial.profile.pomodoroCycle.focusMinutes,
      shortBreakMinutes: initial.profile.pomodoroCycle.shortBreakMinutes,
      longBreakMinutes: initial.profile.pomodoroCycle.longBreakMinutes,
      roundsPerCycle: initial.profile.pomodoroCycle.roundsPerCycle,
    },
    cycleSettingsOpen: false,

    tickHandle: null,
    heartbeatHandle: null,
    connectTimeoutHandle: null,
    clockHandle: null,
    realtimeChannel: null,
    twemojiParseHandle: null,

    // Tab title/favicon while backgrounded: the page's own <title> at load,
    // and the favicon <link> elements (plus their original hrefs) so a
    // background countdown indicator can be applied and then exactly
    // reverted once the tab is foregrounded again or the timer stops.
    originalTitle: null,
    faviconIco: null,
    faviconPng: null,
    faviconSvg: null,
    faviconOriginalIcoHref: null,
    faviconOriginalPngHref: null,

    init() {
      this.startTwemojiObserver()

      this.originalTitle = document.title
      this.faviconIco = document.getElementById('favicon-ico')
      this.faviconPng = document.getElementById('favicon-png')
      this.faviconSvg = document.getElementById('favicon-svg')
      this.faviconOriginalIcoHref = this.faviconIco
        ? this.faviconIco.getAttribute('href')
        : null
      this.faviconOriginalPngHref = this.faviconPng
        ? this.faviconPng.getAttribute('href')
        : null

      this.heldSeatCode = this.deriveHeldSeatCode(this.state)
      this.suppressAlreadyFinishedSound()
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

      this.refreshSky()

      this.clockHandle = setInterval(() => {
        this.clockNow = Date.now()
        this.refreshSky()
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
        this.updateTabIndicators()

        if (!document.hidden) {
          this.refresh()
          this.heartbeat()
        }
      })
    },

    // --- emoji rendering -------------------------------------------------

    // Alpine re-renders x-text/x-for content constantly (seat occupants,
    // the profile card, timer countdowns), so emoji can't be swapped for
    // Twemoji images once at load — they'd only cover whatever happened to
    // be on screen at that instant. A MutationObserver over the whole
    // component instead re-parses after every DOM change, batched onto a
    // single animation frame so a burst of updates (e.g. the once-a-second
    // countdown tick across many seats) triggers one pass, not many.
    // twemoji.parse() is idempotent on already-parsed text (the emoji
    // character is gone, replaced by an <img>), so the mutations it causes
    // don't retrigger themselves into a loop.
    startTwemojiObserver() {
      if (typeof window.twemoji === 'undefined') {
        return
      }

      this.parseTwemoji()

      const observer = new MutationObserver(() => this.scheduleTwemojiParse())
      observer.observe(this.$el, {
        childList: true,
        subtree: true,
        characterData: true,
      })
    },

    scheduleTwemojiParse() {
      if (this.twemojiParseHandle) {
        return
      }

      this.twemojiParseHandle = requestAnimationFrame(() => {
        this.twemojiParseHandle = null
        this.parseTwemoji()
      })
    },

    parseTwemoji() {
      window.twemoji.parse(this.$el)
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
        this.checkTimerFinishedSound()
        this.updateTabIndicators()

        if (!this.hasVisibleCountdown()) {
          clearInterval(this.tickHandle)
          this.tickHandle = null
        }
      }, 1000)
    },

    // A seat already sitting in overtime when the page loads (or the tab
    // regains focus) shouldn't chime right away — only a timer that ends
    // while the student is watching it should.
    suppressAlreadyFinishedSound() {
      const seat = this.mySeat()

      if (
        seat &&
        seat.timerEndsAt &&
        Date.parse(seat.timerEndsAt) <= Date.now()
      ) {
        this.lastNotifiedTimerEndsAt = seat.timerEndsAt
      }
    },

    // Chimes once per timer end (focus or break) for the viewer's own seat,
    // the moment its countdown crosses zero — not on every tick afterwards,
    // since the seat is left sitting in overtime rather than reset.
    checkTimerFinishedSound() {
      if (!this.playSoundOnTimerEnd) {
        return
      }

      const seat = this.mySeat()

      if (!seat || !seat.timerEndsAt) {
        return
      }

      if (Date.parse(seat.timerEndsAt) > this.now) {
        return
      }

      if (this.lastNotifiedTimerEndsAt === seat.timerEndsAt) {
        return
      }

      this.lastNotifiedTimerEndsAt = seat.timerEndsAt
      playTimerFinishedSound()
    },

    hasVisibleCountdown() {
      return this.allSeats().some(
        seat => seat.timerEndsAt !== null || seat.timerStartedAt !== null
      )
    },

    // --- background tab indicators ---------------------------------------

    // Only worth doing while the student has actually switched away: the
    // countdown and seat state are already visible on screen otherwise, so
    // rewriting the tab chrome would just be noise (and a wasted paint).
    updateTabIndicators() {
      const seat = this.mySeat()
      const running = !!seat && (!!seat.timerEndsAt || !!seat.timerStartedAt)

      if (!document.hidden || !running) {
        this.resetTabIndicators()
        return
      }

      document.title =
        this.remainingLabel(seat) +
        ' ' +
        this.timerPhaseLabel() +
        ' - ' +
        this.originalTitle

      this.applyStageFavicon(this.isOnBreak() ? '#10b981' : '#b05139')
    },

    resetTabIndicators() {
      if (
        this.originalTitle !== null &&
        document.title !== this.originalTitle
      ) {
        document.title = this.originalTitle
      }

      this.resetFavicon()
    },

    // Drawn on the fly rather than shipped as static assets: a plain
    // colored dot is enough to tell focus and break apart at a glance in a
    // browser tab strip, and it reuses the same amber/emerald pair already
    // used everywhere else for the two phases.
    buildStageFaviconDataUrl(color) {
      const canvas = document.createElement('canvas')
      canvas.width = 64
      canvas.height = 64
      const ctx = canvas.getContext('2d')

      if (!ctx) {
        return null
      }

      ctx.beginPath()
      ctx.arc(32, 32, 28, 0, Math.PI * 2)
      ctx.fillStyle = color
      ctx.fill()

      return canvas.toDataURL('image/png')
    },

    // The svg icon link is disqualified (rather than rewritten) while a
    // stage color is showing, since a raster dot can't honestly wear
    // type="image/svg+xml" — browsers otherwise keep preferring the
    // untouched svg over the ico/png hrefs this swaps in.
    applyStageFavicon(color) {
      if (!this.faviconIco && !this.faviconPng) {
        return
      }

      const dataUrl = this.buildStageFaviconDataUrl(color)

      if (!dataUrl) {
        return
      }

      if (this.faviconIco) {
        this.faviconIco.setAttribute('href', dataUrl)
      }

      if (this.faviconPng) {
        this.faviconPng.setAttribute('href', dataUrl)
      }

      if (this.faviconSvg) {
        this.faviconSvg.setAttribute('rel', 'alternate icon')
      }
    },

    resetFavicon() {
      if (this.faviconIco && this.faviconOriginalIcoHref) {
        this.faviconIco.setAttribute('href', this.faviconOriginalIcoHref)
      }

      if (this.faviconPng && this.faviconOriginalPngHref) {
        this.faviconPng.setAttribute('href', this.faviconOriginalPngHref)
      }

      if (this.faviconSvg) {
        this.faviconSvg.setAttribute('rel', 'icon')
      }
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
      this.updateTabIndicators()

      // Focus mode is a view of a running timer; without one (stopped,
      // or the seat released underneath us) there's nothing to show.
      if (this.focusMode && !this.hasTimer()) {
        this.closeFocusMode()
      }
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

      // A floor closed (its last occupant left and it's no longer needed)
      // — drop it instead of leaving a stale, no-longer-open floor with
      // frozen seat data rendered on screen.
      if (payload.openFloors < this.state.floors.length) {
        this.state.floors = this.state.floors.slice(0, payload.openFloors)
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

      const wasOccupied = target.isOccupied

      Object.assign(target, incomingSeat, {
        // Fan-out broadcasts always carry isYou: false — derive it
        // ourselves from the seat code we actually hold.
        isYou: incomingSeat.code === this.heldSeatCode,
      })

      // The broadcast payload doesn't carry per-floor occupied counts, so
      // the floor badge (e.g. "24 / 24 人在座") has to be kept in sync here
      // instead of only refreshing on a full state fetch.
      if (target.isOccupied !== wasOccupied) {
        const floor = this.floorForSeat(target.code)

        if (floor) {
          floor.occupiedCount += target.isOccupied ? 1 : -1
        }
      }
    },

    floorForSeat(code) {
      for (const floor of this.state.floors) {
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
    },

    // --- actions --------------------------------------------------------

    async take(code) {
      if (this.busySeatCode || this.heldSeatCode) {
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
        const isPomodoro = this.timerMode === 'pomodoro'
        const cycle = this.normalizedCycle()

        const response = await window.axios.post('/study-room/timer', {
          mode: this.timerMode,
          minutes: this.timerMode === 'custom' ? this.customMinutes : null,
          verb: this.selectedVerb,
          subjectCourseId:
            this.selectedSubjectCourseId === ''
              ? null
              : Number(this.selectedSubjectCourseId),
          focusMinutes: isPomodoro ? cycle.focusMinutes : null,
          shortBreakMinutes: isPomodoro ? cycle.shortBreakMinutes : null,
          longBreakMinutes: isPomodoro ? cycle.longBreakMinutes : null,
          roundsPerCycle: isPomodoro ? cycle.roundsPerCycle : null,
        })
        this.cycle = cycle
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

    async startNextRound() {
      if (this.panelBusy) {
        return
      }

      this.panelBusy = true

      try {
        const response = await window.axios.post('/study-room/timer/next')
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
      const label = this.formatDurationLabel(session.focusSeconds)

      if (!session.overtimeSeconds) {
        return label
      }

      return (
        label +
        '（超時 ' +
        this.formatDurationLabel(session.overtimeSeconds) +
        '）'
      )
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

    // --- sky: windows & garden ---------------------------------------------
    // The floor map's windows (every floor) and the garden outside the
    // ground floor follow the real sun and moon over the campus, in
    // Taiwan — see study-room-sky.js for the model.

    refreshSky() {
      const ms =
        this.skyOverrideMs === null ? this.clockNow : this.skyOverrideMs
      const minute = Math.floor(ms / 60000)

      if (minute === this.skyMinute) {
        return
      }

      this.skyMinute = minute
      this.sky = computeSky(ms, this.config.latitude, this.config.longitude)
      this.pushSkyToCanvas()
    },

    // The garden's sky is drawn by a shader on a canvas sitting between the
    // CSS gradient and the scenery (see study-room-sky-shader.js). It reads
    // the same palette and sun/moon positions as everything else, so it's a
    // richer rendering of the same sky, not a second one — and it draws the
    // sky only. The clouds, skyline and trees in front of it stay the flat
    // SVG and CSS scenery they were.
    //
    // The same sky is drawn twice: on the garden strip ('garden') and, in
    // focus mode, across the whole screen ('focus'). Each has its own
    // canvas, renderer and layout (see SKY_LAYOUTS).
    mountSkyCanvas(canvas, layout = 'garden') {
      this.unmountSkyCanvas(layout)

      // Only flipped on once a frame is really on the canvas, and off
      // again if the context goes away: either way the CSS gradient
      // underneath is what's on show.
      const renderer = createSkyRenderer(
        canvas,
        {
          onFirstPaint: () => {
            this.setSkyCanvasActive(layout, true)
          },
          onContextLost: () => {
            this.setSkyCanvasActive(layout, false)
          },
        },
        SKY_LAYOUTS[layout].shader
      )

      if (!renderer) {
        return
      }

      if (layout === 'focus') {
        this.focusSkyRenderer = renderer
      } else {
        this.skyRenderer = renderer
      }

      this.pushSkyToCanvas()
    },

    unmountSkyCanvas(layout) {
      const renderer =
        layout === 'focus' ? this.focusSkyRenderer : this.skyRenderer

      if (renderer) {
        renderer.destroy()
      }

      if (layout === 'focus') {
        this.focusSkyRenderer = null
      } else {
        this.skyRenderer = null
      }

      this.setSkyCanvasActive(layout, false)
    },

    setSkyCanvasActive(layout, active) {
      if (layout === 'focus') {
        this.focusSkyCanvasActive = active
      } else {
        this.skyCanvasActive = active
      }
    },

    isSkyCanvasActive(layout = 'garden') {
      return layout === 'focus'
        ? this.focusSkyCanvasActive
        : this.skyCanvasActive
    },

    pushSkyToCanvas() {
      if (this.skyRenderer) {
        this.skyRenderer.update(this.skyShaderInputs('garden'))
      }

      if (this.focusSkyRenderer) {
        this.focusSkyRenderer.update(this.skyShaderInputs('focus'))
      }
    },

    // Colours as 0..1 triplets, positions in the same viewport coordinates
    // the sun and moon discs are placed at, so the shader's glow lands
    // exactly where the disc is drawn.
    skyShaderInputs(layout = 'garden') {
      const sky = this.sky
      const sun = this.bodyPoint(sky.sunX, sky.sunY, layout)
      const moon = this.bodyPoint(sky.moonX, sky.moonY, layout)

      return {
        top: this.unitRgb(sky.top),
        mid: this.unitRgb(sky.mid),
        horizon: this.unitRgb(sky.horizon),
        sun: [sun.x, sun.y],
        moon: [moon.x, moon.y],
        sunGlow: sky.sunGlow,
        // Fades the sun's haze out through civil twilight, by which point
        // the palette's own dusk colours have taken over.
        sunStrength: Math.min(1, Math.max(0, (sky.sunAltitudeDeg + 6) / 8)),
        moonGlow: sky.moonVisible ? sky.moonFraction * (1 - sky.daylight) : 0,
        daylight: sky.daylight,
      }
    },

    unitRgb(triplet) {
      return [triplet[0] / 255, triplet[1] / 255, triplet[2] / 255]
    },

    // Held at 0 until the shader has a context and has painted a frame, so
    // a browser without WebGL never shows an empty canvas over the gradient.
    skyCanvasStyle(layout = 'garden') {
      return { opacity: this.isSkyCanvasActive(layout) ? 1 : 0 }
    },

    // Freeze the sky at an instant (ms since epoch), or null to follow the
    // clock again. Used by browser tests and the ?sky-at= URL parameter.
    previewSky(ms) {
      this.skyOverrideMs = ms
      this.skyMinute = null
      this.refreshSky()
    },

    rgb(triplet, alpha = 1) {
      return (
        'rgba(' +
        triplet[0] +
        ',' +
        triplet[1] +
        ',' +
        triplet[2] +
        ',' +
        alpha +
        ')'
      )
    },

    skyPhaseLabel() {
      return SKY_PHASE_LABELS[this.sky.phase]
    },

    gardenAriaLabel() {
      const moon = this.sky.moonVisible
        ? '，月亮' + Math.round(this.sky.moonFraction * 100) + '% 亮'
        : ''

      return '窗外的校園花園與城市，現在是' + this.skyPhaseLabel() + moon
    },

    // Every colour the garden scene uses, as CSS custom properties on its
    // root, so the SVG hills, trees and lawn below all shift together
    // through dusk and night from one palette (see scenePalette).
    gardenVars() {
      const sky = this.sky
      const vars = {
        '--g-sky-top': this.rgb(sky.top),
        '--g-sky-mid': this.rgb(sky.mid),
        '--g-sky-horizon': this.rgb(sky.horizon),
        '--g-cloud-opacity': 0.25 + 0.7 * sky.daylight,
        '--g-firefly': sky.fireflyOpacity,
        '--g-lamp': sky.lampOpacity,
      }

      for (const [name, color] of Object.entries(sky.scene)) {
        vars['--g-' + name] = this.rgb(color)
      }

      return vars
    },

    gardenSkyStyle() {
      return {
        background:
          'linear-gradient(to bottom, var(--g-sky-top) 0%, var(--g-sky-mid) 55%, var(--g-sky-horizon) 100%)',
      }
    },

    // Warm haze around a low sun, anchored to where the sun is drawn.
    sunGlowStyle(layout = 'garden') {
      const position = this.bodyStyle(this.sky.sunX, this.sky.sunY, layout)

      return {
        opacity: this.sky.sunGlow * 0.85,
        background:
          'radial-gradient(ellipse 55% 70% at ' +
          position.left +
          ' ' +
          position.top +
          ', ' +
          this.rgb([255, 190, 110], 0.75) +
          ' 0%, ' +
          this.rgb([255, 140, 90], 0.3) +
          ' 40%, transparent 75%)',
      }
    },

    starsStyle() {
      return { opacity: this.sky.starOpacity }
    },

    starStyle(star, layout = 'garden') {
      const size = layout === 'focus' ? star.size * 1.6 : star.size

      return {
        left: star.left + '%',
        top: star.top + '%',
        width: size + 'px',
        height: size + 'px',
        animationDelay: star.twinkleDelay + 's',
      }
    },

    // Bodies are placed in the sky area above the skyline: x across the
    // view (east on the left), y from resting on the skyline's ridge to
    // near the top when overhead — where exactly depends on the layout.
    bodyPoint(x, y, layout = 'garden') {
      const { zenith, horizon } = SKY_LAYOUTS[layout]

      return { x, y: zenith + (1 - y) * (horizon - zenith) }
    },

    bodyStyle(x, y, layout = 'garden') {
      const point = this.bodyPoint(x, y, layout)

      return {
        left: point.x * 100 + '%',
        top: point.y * 100 + '%',
      }
    },

    sunStyle(layout = 'garden') {
      return this.bodyStyle(this.sky.sunX, this.sky.sunY, layout)
    },

    starsFor(layout = 'garden') {
      return layout === 'focus' ? this.focusStars : this.skyStars
    },

    moonStyle(layout = 'garden') {
      const style = this.bodyStyle(this.sky.moonX, this.sky.moonY, layout)

      // The moon is up in daylight too, just washed out by the sky.
      style.opacity = 0.3 + 0.7 * (1 - this.sky.daylight)

      return style
    },

    // Phase drawn as a sky-coloured disc slid across the lit moon: fully
    // covering it at new moon, fully clear at full moon. Waxing moons are
    // lit on the right (as seen from the northern hemisphere), so the
    // shadow slides off to the left.
    moonShadowStyle() {
      const direction = this.sky.moonPhase < 0.5 ? -1 : 1
      const offset = direction * this.sky.moonFraction * 100

      return {
        transform: 'translateX(' + offset + '%)',
        background: 'var(--g-sky-mid)',
      }
    },

    // The window panes on every floor's back wall show the sky's horizon
    // colour — the same light the garden is under.
    windowPaneStyle() {
      return { background: this.rgb(this.sky.horizon) }
    },

    // Light spilling through the windows onto the floor: sky-coloured in
    // daytime, and at night a cool moonlight wash that grows with how
    // full and how high the moon is (nothing but a faint city glow when
    // the moon is down).
    windowLightStyle() {
      const sky = this.sky
      let color

      if (sky.daylight > 0.05) {
        color = this.rgb(sky.horizon, 0.15 + 0.45 * sky.daylight)
      } else if (sky.moonVisible) {
        const strength = sky.moonFraction * Math.sqrt(sky.moonY)

        color = this.rgb([214, 226, 255], 0.18 + 0.4 * strength)
      } else {
        color = this.rgb([255, 214, 150], 0.1)
      }

      return {
        background: 'linear-gradient(to bottom, ' + color + ', transparent)',
      }
    },

    // --- display helpers --------------------------------------------------

    pad2(n) {
      return String(n).padStart(2, '0')
    },

    // A countdown (timerEndsAt set) shows mm:ss while running and keeps
    // ticking past zero as an overtime count-up, '+mm:ss', instead of
    // freezing — so has the student actually been focusing (or resting)
    // longer than planned. A count-up timer (no timerEndsAt at all) has no
    // planned end to overshoot, so it's shown as a plain mm:ss elapsed
    // count with no '+' prefix — that count *is* the point, not an extra.
    remainingLabel(seat) {
      if (seat.timerEndsAt) {
        const diffMs = Date.parse(seat.timerEndsAt) - this.now

        if (diffMs > 0) {
          const totalSeconds = Math.ceil(diffMs / 1000)

          return (
            this.pad2(Math.floor(totalSeconds / 60)) +
            ':' +
            this.pad2(totalSeconds % 60)
          )
        }

        const overtimeSeconds = Math.floor(-diffMs / 1000)

        return (
          '+' +
          this.pad2(Math.floor(overtimeSeconds / 60)) +
          ':' +
          this.pad2(overtimeSeconds % 60)
        )
      }

      if (seat.timerStartedAt) {
        const elapsedSeconds = Math.max(
          0,
          Math.floor((this.now - Date.parse(seat.timerStartedAt)) / 1000)
        )

        return (
          this.pad2(Math.floor(elapsedSeconds / 60)) +
          ':' +
          this.pad2(elapsedSeconds % 60)
        )
      }

      return ''
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
      if (!seat.timerEndsAt && !seat.timerStartedAt) {
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

    // Turns the floor-map/table timer text green while the seat is resting,
    // matching the emerald used everywhere else for break (progressBarClass,
    // timerPhaseClass).
    seatTimerLabelClass(seat) {
      return seat.timerPhase === 'break'
        ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-warm-500 dark:text-zinc-400'
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

    seatClasses(seat, variant = 'solo') {
      // Solo seats are drawn as reading carrels seen from above: a
      // partition on the top and both sides, a desk (rendered in the
      // template) against the back wall, and an open front where the
      // chair sits. Table seats are single chairs around a shared table,
      // so they're just a small rounded chair shape; the template adds the
      // backrest edge so it faces away from the table.
      const classes =
        variant === 'table'
          ? [
              'relative flex size-9 items-center justify-center rounded-lg border-2 text-center transition',
            ]
          : [
              // Partition walls keep a fixed color regardless of occupancy —
              // only the floor inside changes when someone sits down. The
              // occupied layout stacks one more line (emoji + nickname +
              // timer) than the empty layout (seat number + hint), so the
              // min-height must fit the occupied content or occupied seats
              // grow taller than empty ones — and, since seats sit in a CSS
              // grid, stretch every other seat in their row along with them.
              'group relative flex min-h-[99px] w-full max-w-24 flex-col items-center justify-end gap-0.5 rounded-t-lg border-x-[3px] border-t-[3px] border-warm-300 px-1 pt-6 pb-1.5 text-center transition dark:border-zinc-600',
            ]

      if (this.isMine(seat)) {
        // Your own seat gets a warm amber floor and a small "你" badge on
        // the chair (rendered in the template) — no outline, so it still
        // reads as furniture rather than a form control.
        classes.push(
          variant === 'table'
            ? 'border-amber-400 bg-amber-50 shadow-sm dark:border-amber-600 dark:bg-amber-950/40'
            : 'bg-amber-50 dark:bg-amber-950/30'
        )
      } else if (seat.isOccupied) {
        classes.push(
          variant === 'table'
            ? 'border-warm-400 bg-white shadow-sm dark:border-zinc-500 dark:bg-zinc-800'
            : 'bg-warm-100/80 dark:bg-zinc-800/80'
        )
      } else {
        classes.push(
          variant === 'table'
            ? 'border-warm-300 bg-white/70 hover:border-warm-400 hover:bg-white dark:border-zinc-600 dark:bg-zinc-800/60 dark:hover:border-zinc-500 dark:hover:bg-zinc-800'
            : 'bg-white/60 hover:bg-white hover:border-warm-400 dark:bg-zinc-900/60 dark:hover:bg-zinc-800 dark:hover:border-zinc-500'
        )
      }

      if (this.busySeatCode === seat.code) {
        classes.push('opacity-50 cursor-wait')
      }

      return classes.join(' ')
    },

    // Splits a table's seats into the two rows drawn above and below the
    // table surface, so people sit facing each other across it. Row 0 is
    // the first half of the seats, row 1 the rest.
    tableSeatsRow(table, row) {
      const half = Math.ceil(table.seats.length / 2)

      return row === 0 ? table.seats.slice(0, half) : table.seats.slice(half)
    },

    // Label on a floor's up staircase. Floors open one at a time as the
    // ones below fill up (see ResolveOpenFloorCount) — when the next floor
    // isn't open yet, the stair itself is shown blocked (see
    // isStairBlocked) rather than explained in text here.
    stairHint(floor) {
      const nextFloor = floor.floor + 1

      if (nextFloor > this.config.maxFloors) {
        return '頂樓'
      }

      return '往' + this.floorLabel(nextFloor)
    },

    // Whether the next floor up hasn't opened yet, in which case the stair
    // is drawn barricaded instead of walkable.
    isStairBlocked(floor) {
      const nextFloor = floor.floor + 1

      return (
        nextFloor <= this.config.maxFloors && nextFloor > this.state.openFloors
      )
    },

    floorLabel(floor) {
      const digits = [
        '零',
        '一',
        '二',
        '三',
        '四',
        '五',
        '六',
        '七',
        '八',
        '九',
      ]

      return (digits[floor] || String(floor)) + '樓'
    },

    isGroundFloor(floor) {
      return floor.floor === 1
    },

    stairDownHint(floor) {
      return '往' + this.floorLabel(floor.floor - 1) + ' ↓'
    },

    // --- table seat popover -----------------------------------------------
    // Table chairs are too small to show the occupant's nickname and
    // activity inline, so those live in a popover opened by hovering the
    // chair on desktop or tapping it on touch screens. Occupied table
    // chairs therefore stay enabled and route their click here instead of
    // to take().

    tapTableSeat(seat) {
      if (!seat.isOccupied) {
        this.take(seat.code)
        return
      }

      this.peekSeatCode = this.peekSeatCode === seat.code ? null : seat.code
    },

    peek(seat) {
      if (seat.isOccupied) {
        this.peekSeatCode = seat.code
      }
    },

    unpeek(seat) {
      if (this.peekSeatCode === seat.code) {
        this.peekSeatCode = null
      }
    },

    isPeeking(seat) {
      return seat.isOccupied && this.peekSeatCode === seat.code
    },

    mySeat() {
      if (!this.heldSeatCode) {
        return null
      }

      return (
        this.allSeats().find(seat => seat.code === this.heldSeatCode) || null
      )
    },

    mySeatLabel() {
      const seat = this.mySeat()

      return seat ? seat.label : ''
    },

    myActivityLabel() {
      const seat = this.mySeat()

      return seat && seat.activity ? seat.activity : '專注'
    },

    myRemainingLabel() {
      const seat = this.mySeat()

      return seat ? this.remainingLabel(seat) : ''
    },

    // --- action banner: timer state -----------------------------------------
    // Everything the banner (and focus mode) shows is derived from the held
    // seat's timer columns plus the ticking clock, never stored separately.

    // Any timer at all, in any phase — the banner shows its countdown view
    // rather than the start form.
    hasTimer() {
      const seat = this.mySeat()

      return !!seat && !!seat.timerMode
    },

    isPomodoro() {
      const seat = this.mySeat()

      return !!seat && seat.timerMode === 'pomodoro'
    },

    isCountUp() {
      const seat = this.mySeat()

      return !!seat && seat.timerMode === 'count_up'
    },

    // A count-up timer has no planned end, so there's nothing to show a
    // percent-progress bar against.
    hasCountdownEnd() {
      const seat = this.mySeat()

      return !!seat && seat.timerEndsAt !== null
    },

    isOnBreak() {
      const seat = this.mySeat()

      return !!seat && seat.timerPhase === 'break'
    },

    isBreakFinished() {
      const seat = this.mySeat()

      return (
        this.isOnBreak() &&
        seat.timerEndsAt !== null &&
        Date.parse(seat.timerEndsAt) <= this.now
      )
    },

    canStartBreak() {
      const seat = this.mySeat()

      return !!seat && this.isSeatFinishedFocus(seat)
    },

    // The next round only ever follows a pomodoro break — but can cut it
    // short, so this is true for the whole of the break, not just its end.
    canStartNextRound() {
      return this.isPomodoro() && this.isOnBreak()
    },

    hasRunningTimer() {
      const seat = this.mySeat()

      return !!seat && !!seat.timerMode && !this.isSeatFinishedFocus(seat)
    },

    // 1-based round of the running pomodoro, or 0 for a custom timer.
    currentRound() {
      const seat = this.mySeat()

      return seat && seat.timerRound ? seat.timerRound : 0
    },

    roundsPerCycle() {
      const seat = this.mySeat()

      return seat && seat.roundsPerCycle
        ? seat.roundsPerCycle
        : this.cycle.roundsPerCycle
    },

    // Whether the break after the current round is the long one.
    isLongBreakRound() {
      const round = this.currentRound()

      return round > 0 && round % this.roundsPerCycle() === 0
    },

    // Elapsed fraction of the running phase, 0..1. A focus timer the
    // heartbeat has already finalised has no timerStartedAt any more, and
    // is by definition over.
    timerProgress() {
      const seat = this.mySeat()

      if (!seat || !seat.timerEndsAt) {
        return 0
      }

      if (!seat.timerStartedAt) {
        return 1
      }

      const startedAt = Date.parse(seat.timerStartedAt)
      const endsAt = Date.parse(seat.timerEndsAt)

      if (endsAt <= startedAt) {
        return 1
      }

      return clamp01((this.now - startedAt) / (endsAt - startedAt))
    },

    progressStyle() {
      return { width: this.timerProgress() * 100 + '%' }
    },

    progressPercent() {
      return Math.round(this.timerProgress() * 100)
    },

    progressBarClass() {
      return this.isOnBreak() ? 'bg-emerald-500' : 'bg-amber-500'
    },

    timerPhaseLabel() {
      if (this.isOnBreak()) {
        if (this.isBreakFinished()) {
          return '休息結束'
        }

        return this.isLongBreakRound() ? '長休息' : '休息一下'
      }

      if (this.canStartBreak()) {
        return '這一輪完成了'
      }

      return '專注中'
    },

    timerPhaseClass() {
      return this.isOnBreak()
        ? 'text-emerald-700 dark:text-emerald-400'
        : 'text-amber-700 dark:text-amber-400'
    },

    roundLabel() {
      const round = this.currentRound()

      if (round) {
        return '第 ' + round + ' 輪'
      }

      return this.isCountUp() ? '正數計時' : '倒數計時'
    },

    // "預計 14:55 結束" for the running phase.
    timerEndsAtLabel() {
      const seat = this.mySeat()

      if (!seat || !seat.timerEndsAt) {
        return ''
      }

      const { hour, minute } = window.NouTime.taipeiHM(
        new Date(Date.parse(seat.timerEndsAt))
      )

      return (this.isOnBreak() ? '休息到 ' : '預計 ') + hour + ':' + minute
    },

    nextRoundLabel() {
      const next = this.currentRound() + 1

      return (
        (this.isBreakFinished() ? '開始第 ' : '跳過休息，開始第 ') +
        next +
        ' 輪'
      )
    },

    // One dot per round of the cycle, so a student can see where in it
    // they are: rounds before this one are done, this one is lit while its
    // focus runs (and done once on its break), the rest are still to come.
    cycleDots() {
      const perCycle = this.roundsPerCycle()
      const round = this.currentRound()
      const position = round ? (round - 1) % perCycle : -1
      const dots = []

      for (let index = 0; index < perCycle; index++) {
        let state = 'todo'

        if (index < position || (index === position && this.isOnBreak())) {
          state = 'done'
        } else if (index === position) {
          state = 'current'
        }

        dots.push({ id: index, state })
      }

      return dots
    },

    cycleDotClass(dot) {
      if (dot.state === 'done') {
        return 'bg-amber-500'
      }

      if (dot.state === 'current') {
        return 'bg-amber-500 ring-2 ring-amber-300 dark:ring-amber-700'
      }

      return 'bg-warm-300 dark:bg-zinc-600'
    },

    // --- action banner: pomodoro cycle settings ---------------------------------

    // The cycle as it'll be sent: whole minutes, inside the server's
    // bounds, so a half-typed field never turns into a 422 on 開始.
    normalizedCycle() {
      const config = this.config
      const bound = (value, [min, max], fallback) => {
        const number = Math.round(Number(value))

        if (!Number.isFinite(number)) {
          return fallback
        }

        return Math.min(max, Math.max(min, number))
      }

      return {
        focusMinutes: bound(
          this.cycle.focusMinutes,
          config.timerPomodoroFocusBounds,
          config.timerPomodoroFocusMinutes
        ),
        shortBreakMinutes: bound(
          this.cycle.shortBreakMinutes,
          config.timerPomodoroBreakBounds,
          config.timerPomodoroShortBreakMinutes
        ),
        longBreakMinutes: bound(
          this.cycle.longBreakMinutes,
          config.timerPomodoroBreakBounds,
          config.timerPomodoroLongBreakMinutes
        ),
        roundsPerCycle: bound(
          this.cycle.roundsPerCycle,
          config.timerPomodoroRoundsBounds,
          config.timerPomodoroRoundsPerCycle
        ),
      }
    },

    // Lower (0) or upper (1) bound of a cycle field, for the inputs' min/max.
    cycleBound(field, index) {
      const bounds = {
        focus: this.config.timerPomodoroFocusBounds,
        break: this.config.timerPomodoroBreakBounds,
        rounds: this.config.timerPomodoroRoundsBounds,
      }

      return bounds[field][index]
    },

    openCycleSettings() {
      this.cycleSettingsOpen = true
    },

    closeCycleSettings() {
      this.cycle = this.normalizedCycle()
      this.cycleSettingsOpen = false
    },

    resetCycle() {
      this.cycle = {
        focusMinutes: this.config.timerPomodoroFocusMinutes,
        shortBreakMinutes: this.config.timerPomodoroShortBreakMinutes,
        longBreakMinutes: this.config.timerPomodoroLongBreakMinutes,
        roundsPerCycle: this.config.timerPomodoroRoundsPerCycle,
      }
    },

    // The banner's compact form: "25 / 5 / 30 分 · 4 輪"
    cycleChipLabel() {
      const cycle = this.normalizedCycle()

      return (
        cycle.focusMinutes +
        ' / ' +
        cycle.shortBreakMinutes +
        ' / ' +
        cycle.longBreakMinutes +
        ' 分 · ' +
        cycle.roundsPerCycle +
        ' 輪'
      )
    },

    // "25 分專注 · 5 分休息 · 每 4 輪長休 30 分"
    cycleSummaryLabel() {
      const cycle = this.normalizedCycle()

      return (
        cycle.focusMinutes +
        ' 分專注 · ' +
        cycle.shortBreakMinutes +
        ' 分休息 · 每 ' +
        cycle.roundsPerCycle +
        ' 輪長休 ' +
        cycle.longBreakMinutes +
        ' 分'
      )
    },

    // --- focus mode ----------------------------------------------------------
    // The banner taken over the whole window: what you see from your
    // carrel. The partition wall in front of you with the countdown on
    // it, the window above it with the same sky the garden is under, and
    // your desk with its lamp below. Fills the browser window only — it
    // never asks for the browser's own fullscreen.

    openFocusMode() {
      if (!this.hasTimer()) {
        return
      }

      this.focusMode = true
    },

    closeFocusMode() {
      this.focusMode = false
      this.unmountSkyCanvas('focus')
    },

    // How lit the carrel is, 0 (night: dark wall, desk lamp doing the
    // work) to 1 (a bright day through the window). Every colour in the
    // carrel crossfades on this one number so the wall and the ink on it
    // always contrast — a wider, gentler crossfade lands them both on a
    // muddy mid-tone through dusk.
    focusInk() {
      return clamp01((this.sky.daylight - 0.5) / 0.15)
    },

    // Night → day blend of two colours on the carrel's lighting.
    carrelColor(night, day) {
      const light = this.focusInk()

      return this.rgb(
        night.map((channel, index) =>
          Math.round(channel + (day[index] - channel) * light)
        )
      )
    },

    // The garden's palette plus the carrel's own surfaces, so the window
    // scene and the room around it are lit by the same sky.
    carrelVars() {
      const vars = this.gardenVars()

      vars['--c-wall'] = this.carrelColor([74, 58, 50], [246, 236, 226])
      vars['--c-wall-deep'] = this.carrelColor([56, 43, 37], [236, 222, 208])
      vars['--c-frame'] = this.carrelColor([104, 78, 62], [222, 196, 178])
      vars['--c-desk-top'] = this.carrelColor([96, 66, 48], [226, 184, 146])
      vars['--c-desk-bottom'] = this.carrelColor([58, 40, 30], [196, 142, 100])
      // The desk lamp is on whenever a timer runs, and matters more the
      // darker it is.
      vars['--c-lamp'] = this.hasTimer()
        ? 0.35 + 0.65 * (1 - this.focusInk())
        : 0

      return vars
    },

    // The window frame, its mullions and the sill. Set as an inline colour
    // rather than read from --c-frame: Chrome doesn't always repaint a
    // transitioning background that only changed through a custom property.
    frameStyle() {
      return {
        backgroundColor: this.carrelColor([104, 78, 62], [222, 196, 178]),
      }
    },

    focusInkStyle() {
      return {
        color: this.carrelColor([255, 247, 232], [58, 42, 30]),
      }
    },

    // Buttons and chips on the wall.
    focusChromeClass() {
      return this.focusInk() > 0.5
        ? 'border-black/10 bg-white/50 text-warm-900 hover:bg-white/80'
        : 'border-white/20 bg-white/10 text-white hover:bg-white/20'
    },

    focusProgressTrackClass() {
      return this.focusInk() > 0.5 ? 'bg-black/10' : 'bg-white/15'
    },

    focusDotClass(dot) {
      const lit = this.focusInk() > 0.5 ? 'bg-warm-900' : 'bg-white'
      const dim = this.focusInk() > 0.5 ? 'bg-black/15' : 'bg-white/25'

      if (dot.state === 'todo') {
        return dim
      }

      return dot.state === 'current' ? lit + ' scale-125' : lit
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
