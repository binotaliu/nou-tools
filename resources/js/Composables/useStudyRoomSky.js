import { reactive, ref } from 'vue'
import { buildStarField, computeSky, SKY_PHASE_LABELS } from '../study-room-sky'
import { createSkyRenderer } from '../study-room-sky-shader'

// Standalone composable for the sky/clock/garden/focus-carrel visuals: it's
// self-contained (only needs latitude/longitude from clientConfig) and used
// by several template regions — the entrance wall's garden window, the
// floor map's windows, and the fullscreen focus mode.
//
// No restricted-CSP expression workaround is needed here — Vue SFCs compile
// to plain render functions at build time, so this composable is free to
// use normal JS in its helpers.
function readSkyOverrideFromUrl() {
  const raw = new URLSearchParams(window.location.search).get('sky-at')

  if (!raw) {
    return null
  }

  // URLSearchParams decodes a literal '+' in the query string as a space,
  // so an unencoded offset like '...T18:40:00+08:00' comes back as
  // '...T18:40:00 08:00'. Restore it so plain, copy-pasted ISO strings work.
  const normalized = raw.replace(/ (\d{2}:\d{2})$/, '+$1')
  const ms = Date.parse(normalized)

  return Number.isNaN(ms) ? null : ms
}

const SKY_LAYOUTS = {
  garden: {
    zenith: 0.06,
    horizon: 0.4,
    shader: { horizonY: 0.42, zenithY: 0.02 },
  },
  focus: {
    zenith: 0.1,
    horizon: 0.7,
    shader: { horizonY: 0.72, zenithY: 0.05 },
  },
}

function clamp01(value) {
  return Math.min(1, Math.max(0, value))
}

export default function useStudyRoomSky(config) {
  const clockNow = ref(Date.now())
  const skyOverrideMs = ref(readSkyOverrideFromUrl())
  let skyMinute = null

  const sky = ref(computeSky(clockNow.value, config.latitude, config.longitude))
  const skyStars = buildStarField()
  const focusStars = buildStarField(90)

  const canvases = {
    garden: { renderer: null, active: ref(false) },
    focus: { renderer: null, active: ref(false) },
  }

  let clockHandle = null

  function refreshSky() {
    const ms =
      skyOverrideMs.value === null ? clockNow.value : skyOverrideMs.value
    const minute = Math.floor(ms / 60000)

    if (minute === skyMinute) {
      return
    }

    skyMinute = minute
    sky.value = computeSky(ms, config.latitude, config.longitude)
    pushSkyToCanvas()
  }

  function previewSky(ms) {
    skyOverrideMs.value = ms
    skyMinute = null
    refreshSky()
  }

  function startClock() {
    refreshSky()
    clockHandle = setInterval(() => {
      clockNow.value = Date.now()
      refreshSky()
    }, 1000)
  }

  function stopClock() {
    if (clockHandle) {
      clearInterval(clockHandle)
      clockHandle = null
    }
  }

  function mountSkyCanvas(canvas, layout = 'garden') {
    unmountSkyCanvas(layout)

    const entry = canvases[layout]
    const renderer = createSkyRenderer(
      canvas,
      {
        onFirstPaint: () => {
          entry.active.value = true
        },
        onContextLost: () => {
          entry.active.value = false
        },
      },
      SKY_LAYOUTS[layout].shader
    )

    if (!renderer) {
      return
    }

    entry.renderer = renderer
    pushSkyToCanvas()
  }

  function unmountSkyCanvas(layout) {
    const entry = canvases[layout]

    if (entry.renderer) {
      entry.renderer.destroy()
      entry.renderer = null
    }

    entry.active.value = false
  }

  function isSkyCanvasActive(layout = 'garden') {
    return canvases[layout].active.value
  }

  function pushSkyToCanvas() {
    for (const layout of Object.keys(canvases)) {
      if (canvases[layout].renderer) {
        canvases[layout].renderer.update(skyShaderInputs(layout))
      }
    }
  }

  function bodyPoint(x, y, layout = 'garden') {
    const { zenith, horizon } = SKY_LAYOUTS[layout]

    return { x, y: zenith + (1 - y) * (horizon - zenith) }
  }

  function bodyStyle(x, y, layout = 'garden') {
    const point = bodyPoint(x, y, layout)

    return { left: point.x * 100 + '%', top: point.y * 100 + '%' }
  }

  function unitRgb(triplet) {
    return [triplet[0] / 255, triplet[1] / 255, triplet[2] / 255]
  }

  function skyShaderInputs(layout = 'garden') {
    const s = sky.value
    const sun = bodyPoint(s.sunX, s.sunY, layout)
    const moon = bodyPoint(s.moonX, s.moonY, layout)

    return {
      top: unitRgb(s.top),
      mid: unitRgb(s.mid),
      horizon: unitRgb(s.horizon),
      sun: [sun.x, sun.y],
      moon: [moon.x, moon.y],
      sunGlow: s.sunGlow,
      sunStrength: Math.min(1, Math.max(0, (s.sunAltitudeDeg + 6) / 8)),
      moonGlow: s.moonVisible ? s.moonFraction * (1 - s.daylight) : 0,
      daylight: s.daylight,
    }
  }

  function rgb(triplet, alpha = 1) {
    return `rgba(${triplet[0]},${triplet[1]},${triplet[2]},${alpha})`
  }

  function skyPhaseLabel() {
    return SKY_PHASE_LABELS[sky.value.phase]
  }

  function gardenAriaLabel() {
    const moon = sky.value.moonVisible
      ? '，月亮' + Math.round(sky.value.moonFraction * 100) + '% 亮'
      : ''

    return '窗外的校園花園與城市，現在是' + skyPhaseLabel() + moon
  }

  function gardenVars() {
    const s = sky.value
    const vars = {
      '--g-sky-top': rgb(s.top),
      '--g-sky-mid': rgb(s.mid),
      '--g-sky-horizon': rgb(s.horizon),
      '--g-cloud-opacity': 0.25 + 0.7 * s.daylight,
      '--g-firefly': s.fireflyOpacity,
      '--g-lamp': s.lampOpacity,
    }

    for (const [name, color] of Object.entries(s.scene)) {
      vars['--g-' + name] = rgb(color)
    }

    return vars
  }

  function gardenSkyStyle() {
    return {
      background:
        'linear-gradient(to bottom, var(--g-sky-top) 0%, var(--g-sky-mid) 55%, var(--g-sky-horizon) 100%)',
    }
  }

  function sunGlowStyle(layout = 'garden') {
    const position = bodyStyle(sky.value.sunX, sky.value.sunY, layout)

    return {
      opacity: sky.value.sunGlow * 0.85,
      background:
        `radial-gradient(ellipse 55% 70% at ${position.left} ${position.top}, ` +
        `${rgb([255, 190, 110], 0.75)} 0%, ${rgb([255, 140, 90], 0.3)} 40%, transparent 75%)`,
    }
  }

  function starsStyle() {
    return { opacity: sky.value.starOpacity }
  }

  function starStyle(star, layout = 'garden') {
    const size = layout === 'focus' ? star.size * 1.6 : star.size

    return {
      left: star.left + '%',
      top: star.top + '%',
      width: size + 'px',
      height: size + 'px',
      animationDelay: star.twinkleDelay + 's',
    }
  }

  function starsFor(layout = 'garden') {
    return layout === 'focus' ? focusStars : skyStars
  }

  function sunStyle(layout = 'garden') {
    return bodyStyle(sky.value.sunX, sky.value.sunY, layout)
  }

  function moonStyle(layout = 'garden') {
    const style = bodyStyle(sky.value.moonX, sky.value.moonY, layout)

    style.opacity =
      sky.value.moonFraction * (0.3 + 0.7 * (1 - sky.value.daylight))

    return style
  }

  function moonShadowStyle() {
    const direction = sky.value.moonPhase < 0.5 ? -1 : 1
    const offset = direction * sky.value.moonFraction * 100

    return {
      transform: 'translateX(' + offset + '%)',
      background: 'var(--g-sky-mid)',
    }
  }

  function windowPaneStyle() {
    return { background: rgb(sky.value.horizon) }
  }

  function windowLightStyle() {
    const s = sky.value
    let color

    if (s.daylight > 0.05) {
      color = rgb(s.horizon, 0.15 + 0.45 * s.daylight)
    } else if (s.moonVisible) {
      const strength = s.moonFraction * Math.sqrt(s.moonY)
      color = rgb([214, 226, 255], 0.18 + 0.4 * strength)
    } else {
      color = rgb([255, 214, 150], 0.1)
    }

    return {
      background: 'linear-gradient(to bottom, ' + color + ', transparent)',
    }
  }

  function skyCanvasStyle(layout = 'garden') {
    return { opacity: isSkyCanvasActive(layout) ? 1 : 0 }
  }

  // --- focus-mode carrel lighting ---

  function focusInk() {
    return clamp01((sky.value.daylight - 0.5) / 0.15)
  }

  function carrelColor(night, day) {
    const light = focusInk()

    return rgb(
      night.map((channel, index) =>
        Math.round(channel + (day[index] - channel) * light)
      )
    )
  }

  function carrelVars(hasTimer) {
    const vars = gardenVars()

    vars['--c-wall'] = carrelColor([74, 58, 50], [246, 236, 226])
    vars['--c-wall-deep'] = carrelColor([56, 43, 37], [236, 222, 208])
    vars['--c-frame'] = carrelColor([104, 78, 62], [222, 196, 178])
    vars['--c-desk-top'] = carrelColor([96, 66, 48], [226, 184, 146])
    vars['--c-desk-bottom'] = carrelColor([58, 40, 30], [196, 142, 100])
    vars['--c-lamp'] = hasTimer ? 0.35 + 0.65 * (1 - focusInk()) : 0

    return vars
  }

  function frameStyle() {
    return { backgroundColor: carrelColor([104, 78, 62], [222, 196, 178]) }
  }

  function focusInkStyle() {
    return { color: carrelColor([255, 247, 232], [58, 42, 30]) }
  }

  function focusChromeClass() {
    return focusInk() > 0.5
      ? 'border-black/10 bg-white/50 text-theme-900 hover:bg-white/80'
      : 'border-white/20 bg-white/10 text-white hover:bg-white/20'
  }

  function focusProgressTrackClass() {
    return focusInk() > 0.5 ? 'bg-black/10' : 'bg-white/15'
  }

  function focusDotClass(dot) {
    const lit = focusInk() > 0.5 ? 'bg-theme-900' : 'bg-white'
    const dim = focusInk() > 0.5 ? 'bg-black/15' : 'bg-white/25'

    if (dot.state === 'todo') {
      return dim
    }

    return dot.state === 'current' ? lit + ' scale-125' : lit
  }

  // --- clock face ---

  function clockTimeLabel() {
    const { hour, minute } = window.NouTime.taipeiHM(new Date(clockNow.value))

    return hour + ':' + minute
  }

  function clockDateLabel() {
    const ymd = window.NouTime.taipeiYmd(new Date(clockNow.value))

    return (
      window.NouTime.monthDay(ymd) + ' 週' + window.NouTime.weekdayFromYmd(ymd)
    )
  }

  function clockRotation(degrees) {
    return { transform: 'translateX(-50%) rotate(' + degrees + 'deg)' }
  }

  function clockHandAngles() {
    const date = new Date(clockNow.value)
    const { hour, minute } = window.NouTime.taipeiHM(date)
    const minutes = Number(minute) + date.getSeconds() / 60
    const hours = (Number(hour) % 12) + minutes / 60

    return {
      hour: hours * 30,
      minute: minutes * 6,
      second: date.getSeconds() * 6,
    }
  }

  function clockHandStyle(hand) {
    return clockRotation(clockHandAngles()[hand])
  }

  function clockTickStyle(tick) {
    return clockRotation(tick * 30)
  }

  function clockTickClass(tick) {
    return tick % 3 === 0 ? 'mt-1 h-1.5' : 'mt-1.5 h-1 opacity-60'
  }

  return reactive({
    clockNow,
    sky,
    // Exposed for tests/Browser/StudyRoomTest.php's shader-sampling
    // assertions.
    get skyRenderer() {
      return canvases.garden.renderer
    },
    get skyCanvasActive() {
      return canvases.garden.active.value
    },
    startClock,
    stopClock,
    refreshSky,
    previewSky,
    mountSkyCanvas,
    unmountSkyCanvas,
    isSkyCanvasActive,
    starsFor,
    skyPhaseLabel,
    gardenAriaLabel,
    gardenVars,
    gardenSkyStyle,
    sunGlowStyle,
    starsStyle,
    starStyle,
    sunStyle,
    moonStyle,
    moonShadowStyle,
    windowPaneStyle,
    windowLightStyle,
    skyCanvasStyle,
    focusInk,
    carrelVars,
    frameStyle,
    focusInkStyle,
    focusChromeClass,
    focusProgressTrackClass,
    focusDotClass,
    clockTimeLabel,
    clockDateLabel,
    clockHandStyle,
    clockTickStyle,
    clockTickClass,
    rgb,
  })
}
