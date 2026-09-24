import { reactive, ref } from 'vue'
import { playTimerFinishedSound } from '../study-room-sound'

// The Study Room page's <Head title> (resources/js/Pages/StudyRoom/Show.vue)
// is a static string with no dynamic segment, so it can be hardcoded here
// instead of sniffed back out of document.title on every tick.
const STUDY_ROOM_TITLE = '自習室 - NOU 小幫手'

// Everything the banner (and focus mode) shows is derived from the held
// seat's timer columns plus the ticking clock, never stored separately —
// see socket.mySeat().
// `profile` is the reactive object returned by useStudyRoomProfile — read
// here as `profile.playSoundOnTimerEnd` (a plain reactive property, not a
// ref) rather than passed in as a ref, since that's what the profile
// composable's own `reactive({...})` return exposes.
export default function useStudyTimer(
  socket,
  config,
  initialCycle,
  profile,
  verbs = [],
  subjects = []
) {
  const now = ref(Date.now())
  const panelBusy = ref(false)
  const errorMessage = ref(null)

  const timerMode = ref('pomodoro')
  const customMinutes = ref(config.timerCustomMinMinutes)
  // Kept as a string (matching the <select> option values, including '' for
  // the 其他 sentinel) rather than a number, so v-model doesn't have to
  // juggle two different JS types before/after a user pick — it's only
  // converted back to number|null right before it's sent.
  const selectedVerb = ref(verbs.length ? verbs[0].value : null)
  const selectedSubjectCourseId = ref(
    subjects.length && subjects[0].id !== null ? String(subjects[0].id) : ''
  )

  const cycle = ref({ ...initialCycle })
  const cycleSettingsOpen = ref(false)

  const activityModalOpen = ref(false)
  const changeVerb = ref(null)
  const changeSubjectCourseId = ref('')

  const focusMode = ref(false)

  let tickHandle = null
  let lastNotifiedTimerEndsAt = null

  function pad2(n) {
    return String(n).padStart(2, '0')
  }

  function clockLabel(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = totalSeconds % 60

    if (minutes < 60) {
      return pad2(minutes) + ':' + pad2(seconds)
    }

    const hours = Math.floor(minutes / 60)

    return hours + ':' + pad2(minutes % 60) + ':' + pad2(seconds)
  }

  // A paused timer's clock is frozen at the moment it was paused, so its
  // countdown, count-up and progress bar all stop there instead of ticking.
  function clockNow(seat) {
    return seat.pausedAt ? Date.parse(seat.pausedAt) : now.value
  }

  function remainingLabel(seat) {
    if (seat.timerEndsAt) {
      const diffMs = Date.parse(seat.timerEndsAt) - clockNow(seat)

      if (diffMs > 0) {
        return clockLabel(Math.ceil(diffMs / 1000))
      }

      return '+' + clockLabel(Math.floor(-diffMs / 1000))
    }

    if (seat.timerStartedAt) {
      const elapsedSeconds = Math.max(
        0,
        Math.floor((clockNow(seat) - Date.parse(seat.timerStartedAt)) / 1000)
      )

      return clockLabel(elapsedSeconds)
    }

    return ''
  }

  function timerLabel(seat) {
    if (!seat.timerEndsAt && !seat.timerStartedAt) {
      return '--:--'
    }

    return remainingLabel(seat)
  }

  // The spoken counterpart of timerLabel() for seat names. It is rounded to
  // whole minutes so the name only changes once a minute: a screen reader
  // re-reads names it sees change, and mm:ss would change every second.
  function spokenTimerLabel(seat) {
    if (seat.timerEndsAt) {
      const diffMs = Date.parse(seat.timerEndsAt) - clockNow(seat)

      if (diffMs <= 0) {
        return '時間到了'
      }

      return '剩約 ' + Math.ceil(diffMs / 60000) + ' 分鐘'
    }

    if (seat.timerStartedAt) {
      const elapsedMinutes = Math.floor(
        Math.max(0, clockNow(seat) - Date.parse(seat.timerStartedAt)) / 60000
      )

      return '已經 ' + elapsedMinutes + ' 分鐘'
    }

    return ''
  }

  function isSeatFinishedFocus(seat) {
    return (
      seat.timerPhase === 'focus' &&
      !seat.pausedAt &&
      seat.timerEndsAt !== null &&
      Date.parse(seat.timerEndsAt) <= now.value
    )
  }

  function hasVisibleCountdown() {
    return socket
      .allSeats()
      .some(seat => seat.timerEndsAt !== null || seat.timerStartedAt !== null)
  }

  function restartTickIfNeeded() {
    if (tickHandle || !hasVisibleCountdown()) {
      return
    }

    tickHandle = setInterval(() => {
      now.value = Date.now()
      checkTimerFinishedSound()
      updateTabIndicators()

      if (!hasVisibleCountdown()) {
        clearInterval(tickHandle)
        tickHandle = null
      }
    }, 1000)
  }

  function suppressAlreadyFinishedSound() {
    const seat = socket.mySeat()

    if (
      seat &&
      seat.timerEndsAt &&
      Date.parse(seat.timerEndsAt) <= Date.now()
    ) {
      lastNotifiedTimerEndsAt = seat.timerEndsAt
    }
  }

  function checkTimerFinishedSound() {
    if (!profile.playSoundOnTimerEnd) {
      return
    }

    const seat = socket.mySeat()

    if (!seat || !seat.timerEndsAt || seat.pausedAt) {
      return
    }

    if (Date.parse(seat.timerEndsAt) > now.value) {
      return
    }

    if (lastNotifiedTimerEndsAt === seat.timerEndsAt) {
      return
    }

    lastNotifiedTimerEndsAt = seat.timerEndsAt

    if (document.hidden) {
      return
    }

    playTimerFinishedSound()
  }

  // --- background tab indicators ---

  let faviconIco = null
  let faviconPng = null
  let faviconSvg = null
  let faviconOriginalIcoHref = null
  let faviconOriginalPngHref = null

  function initTabIndicators() {
    faviconIco = document.getElementById('favicon-ico')
    faviconPng = document.getElementById('favicon-png')
    faviconSvg = document.getElementById('favicon-svg')
    faviconOriginalIcoHref = faviconIco ? faviconIco.getAttribute('href') : null
    faviconOriginalPngHref = faviconPng ? faviconPng.getAttribute('href') : null
  }

  function updateTabIndicators() {
    const seat = socket.mySeat()
    const running = !!seat && (!!seat.timerEndsAt || !!seat.timerStartedAt)

    if (!document.hidden || !running) {
      resetTabIndicators()
      return
    }

    document.title =
      remainingLabel(seat) + ' ' + timerPhaseLabel() + ' - ' + STUDY_ROOM_TITLE

    applyStageFavicon(isOnBreak() ? '#10b981' : '#b05139')
  }

  function resetTabIndicators() {
    if (document.title !== STUDY_ROOM_TITLE) {
      document.title = STUDY_ROOM_TITLE
    }

    resetFavicon()
  }

  function buildStageFaviconDataUrl(color) {
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
  }

  function applyStageFavicon(color) {
    if (!faviconIco && !faviconPng) {
      return
    }

    const dataUrl = buildStageFaviconDataUrl(color)

    if (!dataUrl) {
      return
    }

    if (faviconIco) {
      faviconIco.setAttribute('href', dataUrl)
    }

    if (faviconPng) {
      faviconPng.setAttribute('href', dataUrl)
    }

    if (faviconSvg) {
      faviconSvg.setAttribute('rel', 'alternate icon')
    }
  }

  function resetFavicon() {
    if (faviconIco && faviconOriginalIcoHref) {
      faviconIco.setAttribute('href', faviconOriginalIcoHref)
    }

    if (faviconPng && faviconOriginalPngHref) {
      faviconPng.setAttribute('href', faviconOriginalPngHref)
    }

    if (faviconSvg) {
      faviconSvg.setAttribute('rel', 'icon')
    }
  }

  // --- derived timer state (over socket.mySeat()) ---

  function hasTimer() {
    const seat = socket.mySeat()

    return !!seat && !!seat.timerMode
  }

  function isPomodoro() {
    const seat = socket.mySeat()

    return !!seat && seat.timerMode === 'pomodoro'
  }

  function isCountUp() {
    const seat = socket.mySeat()

    return !!seat && seat.timerMode === 'count_up'
  }

  function hasCountdownEnd() {
    const seat = socket.mySeat()

    return !!seat && seat.timerEndsAt !== null
  }

  function isOnBreak() {
    const seat = socket.mySeat()

    return !!seat && seat.timerPhase === 'break'
  }

  function isBreakFinished() {
    const seat = socket.mySeat()

    return (
      isOnBreak() &&
      seat.timerEndsAt !== null &&
      Date.parse(seat.timerEndsAt) <= now.value
    )
  }

  function isPaused() {
    const seat = socket.mySeat()

    return !!seat && !!seat.pausedAt
  }

  function canPause() {
    const seat = socket.mySeat()

    return !!seat && seat.timerPhase === 'focus' && !seat.pausedAt
  }

  function isFocusFinished() {
    const seat = socket.mySeat()

    return !!seat && isSeatFinishedFocus(seat)
  }

  function canStartBreak() {
    const seat = socket.mySeat()

    return (
      !!seat &&
      (isSeatFinishedFocus(seat) ||
        (isPomodoro() && seat.timerPhase === 'focus' && !seat.pausedAt))
    )
  }

  function canStartNextRound() {
    return isPomodoro() && isOnBreak()
  }

  function canChangeActivity() {
    const seat = socket.mySeat()

    return !!seat && seat.timerPhase === 'focus'
  }

  function currentRound() {
    const seat = socket.mySeat()

    return seat && seat.timerRound ? seat.timerRound : 0
  }

  function roundsPerCycle() {
    const seat = socket.mySeat()

    return seat && seat.roundsPerCycle
      ? seat.roundsPerCycle
      : cycle.value.roundsPerCycle
  }

  function isLongBreakRound() {
    const round = currentRound()

    return round > 0 && round % roundsPerCycle() === 0
  }

  function clamp01(value) {
    return Math.min(1, Math.max(0, value))
  }

  function timerProgress() {
    const seat = socket.mySeat()

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

    return clamp01((clockNow(seat) - startedAt) / (endsAt - startedAt))
  }

  function progressStyle() {
    return { width: timerProgress() * 100 + '%' }
  }

  function progressPercent() {
    return Math.round(timerProgress() * 100)
  }

  function progressBarClass() {
    return isOnBreak() ? 'bg-emerald-500' : 'bg-amber-500'
  }

  function timerPhaseLabel() {
    if (isOnBreak()) {
      if (isBreakFinished()) {
        return '休息結束'
      }

      return isLongBreakRound() ? '長休息' : '休息一下'
    }

    if (isPaused()) {
      return '已暫停'
    }

    if (isFocusFinished()) {
      return '這一輪完成了'
    }

    return '專注中'
  }

  function timerPhaseClass() {
    return isOnBreak()
      ? 'text-emerald-700 dark:text-emerald-400'
      : 'text-amber-700 dark:text-amber-400'
  }

  function roundLabel() {
    const round = currentRound()

    if (round) {
      return '第 ' + round + ' 輪'
    }

    return isCountUp() ? '正數計時' : '倒數計時'
  }

  function timerEndsAtLabel() {
    const seat = socket.mySeat()

    if (!seat || !seat.timerEndsAt) {
      return ''
    }

    if (seat.pausedAt) {
      return '時間已停止'
    }

    const { hour, minute } = window.NouTime.taipeiHM(
      new Date(Date.parse(seat.timerEndsAt))
    )

    return (isOnBreak() ? '休息到 ' : '預計 ') + hour + ':' + minute
  }

  function startBreakLabel() {
    return isFocusFinished() ? '開始休息' : '立即開始休息'
  }

  function nextRoundLabel() {
    return isBreakFinished() ? '開始專注' : '跳過休息'
  }

  function cycleDots() {
    const perCycle = roundsPerCycle()
    const round = currentRound()
    const position = round ? (round - 1) % perCycle : -1
    const dots = []

    for (let index = 0; index < perCycle; index++) {
      let dotState = 'todo'

      if (index < position || (index === position && isOnBreak())) {
        dotState = 'done'
      } else if (index === position) {
        dotState = 'current'
      }

      dots.push({ id: index, state: dotState })
    }

    return dots
  }

  function cycleDotClass(dot) {
    if (dot.state === 'done') {
      return 'bg-amber-500'
    }

    if (dot.state === 'current') {
      return 'bg-amber-500 ring-2 ring-amber-300 dark:ring-amber-700'
    }

    return 'bg-theme-300 dark:bg-zinc-600'
  }

  function normalizedCycle() {
    const bound = (value, [min, max], fallback) => {
      const number = Math.round(Number(value))

      if (!Number.isFinite(number)) {
        return fallback
      }

      return Math.min(max, Math.max(min, number))
    }

    return {
      focusMinutes: bound(
        cycle.value.focusMinutes,
        config.timerPomodoroFocusBounds,
        config.timerPomodoroFocusMinutes
      ),
      shortBreakMinutes: bound(
        cycle.value.shortBreakMinutes,
        config.timerPomodoroBreakBounds,
        config.timerPomodoroShortBreakMinutes
      ),
      longBreakMinutes: bound(
        cycle.value.longBreakMinutes,
        config.timerPomodoroBreakBounds,
        config.timerPomodoroLongBreakMinutes
      ),
      roundsPerCycle: bound(
        cycle.value.roundsPerCycle,
        config.timerPomodoroRoundsBounds,
        config.timerPomodoroRoundsPerCycle
      ),
    }
  }

  function cycleBound(field, index) {
    const bounds = {
      focus: config.timerPomodoroFocusBounds,
      break: config.timerPomodoroBreakBounds,
      rounds: config.timerPomodoroRoundsBounds,
    }

    return bounds[field][index]
  }

  function openCycleSettings() {
    cycleSettingsOpen.value = true
  }

  function closeCycleSettings() {
    cycle.value = normalizedCycle()
    cycleSettingsOpen.value = false
  }

  function resetCycle() {
    cycle.value = {
      focusMinutes: config.timerPomodoroFocusMinutes,
      shortBreakMinutes: config.timerPomodoroShortBreakMinutes,
      longBreakMinutes: config.timerPomodoroLongBreakMinutes,
      roundsPerCycle: config.timerPomodoroRoundsPerCycle,
    }
  }

  function cycleChipLabel() {
    const c = normalizedCycle()

    return `${c.focusMinutes} / ${c.shortBreakMinutes} / ${c.longBreakMinutes} 分 · ${c.roundsPerCycle} 輪`
  }

  function cycleSummaryLabel() {
    const c = normalizedCycle()

    return `${c.focusMinutes} 分專注 · ${c.shortBreakMinutes} 分休息 · 每 ${c.roundsPerCycle} 輪長休 ${c.longBreakMinutes} 分`
  }

  function openChangeActivity() {
    const seat = socket.mySeat()

    changeVerb.value = seat ? seat.activityVerb : null
    changeSubjectCourseId.value =
      seat && seat.subjectCourseId !== null ? seat.subjectCourseId : ''
    activityModalOpen.value = true
  }

  function closeChangeActivity() {
    activityModalOpen.value = false
  }

  function mySeatLabel() {
    const seat = socket.mySeat()

    return seat ? seat.label : ''
  }

  function myActivityLabel() {
    const seat = socket.mySeat()

    return seat && seat.activity ? seat.activity : '專注'
  }

  function myRemainingLabel() {
    const seat = socket.mySeat()

    return seat ? remainingLabel(seat) : ''
  }

  function mySpokenRemaining() {
    const seat = socket.mySeat()

    return seat ? spokenTimerLabel(seat) : ''
  }

  // Everything the panel shows about your timer, as one sentence for the
  // 朗讀目前狀態 accesskey.
  function statusSentence() {
    const seat = socket.mySeat()

    if (!seat) {
      return '你還沒有入座'
    }

    if (!seat.timerMode) {
      return '你坐在 ' + seat.label + '，還沒開始計時'
    }

    return [
      timerPhaseLabel(),
      myActivityLabel(),
      spokenTimerLabel(seat),
      roundLabel(),
      seat.label,
    ]
      .filter(Boolean)
      .join('，')
  }

  function formatDurationLabel(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)

    return hours > 0 ? hours + ' 小時 ' + minutes + ' 分' : minutes + ' 分'
  }

  // --- actions ---

  async function runPanelAction(request) {
    if (panelBusy.value) {
      return
    }

    panelBusy.value = true

    try {
      const response = await request()
      socket.setState(response.data.state)
      errorMessage.value = null
    } catch (error) {
      errorMessage.value = socket.resolveErrorMessage(error)
    } finally {
      panelBusy.value = false
    }
  }

  async function startTimer() {
    await runPanelAction(async () => {
      const isPomo = timerMode.value === 'pomodoro'
      const normalized = normalizedCycle()

      const response = await window.axios.post('/study-room/timer', {
        mode: timerMode.value,
        minutes: timerMode.value === 'custom' ? customMinutes.value : null,
        verb: selectedVerb.value,
        subjectCourseId:
          selectedSubjectCourseId.value === ''
            ? null
            : Number(selectedSubjectCourseId.value),
        focusMinutes: isPomo ? normalized.focusMinutes : null,
        shortBreakMinutes: isPomo ? normalized.shortBreakMinutes : null,
        longBreakMinutes: isPomo ? normalized.longBreakMinutes : null,
        roundsPerCycle: isPomo ? normalized.roundsPerCycle : null,
      })

      cycle.value = normalized

      return response
    })
  }

  async function stopTimer() {
    await runPanelAction(() => window.axios.delete('/study-room/timer'))
  }

  async function pauseTimer() {
    await runPanelAction(() => window.axios.post('/study-room/timer/pause'))
  }

  async function resumeTimer() {
    await runPanelAction(() => window.axios.post('/study-room/timer/resume'))
  }

  async function startBreak() {
    await runPanelAction(() => window.axios.post('/study-room/timer/break'))
  }

  async function startNextRound() {
    await runPanelAction(() => window.axios.post('/study-room/timer/next'))
  }

  async function changeActivity() {
    await runPanelAction(async () => {
      const response = await window.axios.patch('/study-room/timer/activity', {
        verb: changeVerb.value,
        subjectCourseId:
          changeSubjectCourseId.value === ''
            ? null
            : Number(changeSubjectCourseId.value),
      })
      closeChangeActivity()

      return response
    })
  }

  async function leave() {
    await runPanelAction(() => socket.leave())
  }

  function openFocusMode() {
    if (!hasTimer()) {
      return
    }

    focusMode.value = true
  }

  function closeFocusMode(unmountFocusSky) {
    focusMode.value = false

    if (unmountFocusSky) {
      unmountFocusSky()
    }
  }

  // Called whenever the socket's state changes: restarts the tick if a
  // countdown appeared, and closes focus mode if the seat we were watching
  // was released out from under us.
  function onRoomStateChanged(unmountFocusSky) {
    restartTickIfNeeded()
    suppressAlreadyFinishedSound()

    if (focusMode.value && !hasTimer()) {
      closeFocusMode(unmountFocusSky)
    }
  }

  return reactive({
    now,
    panelBusy,
    errorMessage,
    timerMode,
    customMinutes,
    selectedVerb,
    selectedSubjectCourseId,
    cycle,
    cycleSettingsOpen,
    activityModalOpen,
    changeVerb,
    changeSubjectCourseId,
    focusMode,
    initTabIndicators,
    updateTabIndicators,
    resetTabIndicators,
    restartTickIfNeeded,
    suppressAlreadyFinishedSound,
    onRoomStateChanged,
    clockLabel,
    remainingLabel,
    timerLabel,
    spokenTimerLabel,
    isSeatFinishedFocus,
    hasTimer,
    isPomodoro,
    isCountUp,
    hasCountdownEnd,
    isOnBreak,
    isBreakFinished,
    isPaused,
    canPause,
    canStartBreak,
    startBreakLabel,
    canStartNextRound,
    canChangeActivity,
    currentRound,
    roundsPerCycle,
    isLongBreakRound,
    timerProgress,
    progressStyle,
    progressPercent,
    progressBarClass,
    timerPhaseLabel,
    timerPhaseClass,
    roundLabel,
    timerEndsAtLabel,
    nextRoundLabel,
    cycleDots,
    cycleDotClass,
    normalizedCycle,
    cycleBound,
    openCycleSettings,
    closeCycleSettings,
    resetCycle,
    cycleChipLabel,
    cycleSummaryLabel,
    openChangeActivity,
    closeChangeActivity,
    mySeatLabel,
    myActivityLabel,
    myRemainingLabel,
    mySpokenRemaining,
    statusSentence,
    formatDurationLabel,
    startTimer,
    stopTimer,
    pauseTimer,
    resumeTimer,
    startBreak,
    startNextRound,
    changeActivity,
    leave,
    openFocusMode,
    closeFocusMode,
  })
}
