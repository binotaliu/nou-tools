// Sun and moon model for the 自習室 floor map. The windows and the
// ground-floor garden follow the *real* sky over the campus (see
// config/study-room.php `location`) so the room reads as daytime, dusk or
// night the way it would in Taiwan, regardless of the viewer's own zone —
// an instant (ms since epoch) is timezone-free, so no Taipei conversion is
// needed here: the longitude already pins the sky to Taiwan.
//
// Positions use the low-precision formulas from Astronomy Answers
// (aa.quae.nl/en/reken/zonpositie.html) as popularised by suncalc: good to
// roughly a degree, far more than a 6px window pane can show.

const RAD = Math.PI / 180
const DAY_MS = 86400000
const J1970 = 2440588
const J2000 = 2451545
// Obliquity of the ecliptic.
const OBLIQUITY = RAD * 23.4397

function toDays(ms) {
  return ms / DAY_MS - 0.5 + J1970 - J2000
}

function rightAscension(l, b) {
  return Math.atan2(
    Math.sin(l) * Math.cos(OBLIQUITY) - Math.tan(b) * Math.sin(OBLIQUITY),
    Math.cos(l)
  )
}

function declination(l, b) {
  return Math.asin(
    Math.sin(b) * Math.cos(OBLIQUITY) +
      Math.cos(b) * Math.sin(OBLIQUITY) * Math.sin(l)
  )
}

// Azimuth measured from south, positive towards west (suncalc convention).
function azimuth(H, phi, dec) {
  return Math.atan2(
    Math.sin(H),
    Math.cos(H) * Math.sin(phi) - Math.tan(dec) * Math.cos(phi)
  )
}

function altitude(H, phi, dec) {
  return Math.asin(
    Math.sin(phi) * Math.sin(dec) + Math.cos(phi) * Math.cos(dec) * Math.cos(H)
  )
}

function siderealTime(d, lw) {
  return RAD * (280.16 + 360.9856235 * d) - lw
}

function sunCoords(d) {
  const M = RAD * (357.5291 + 0.98560028 * d)
  const C =
    RAD *
    (1.9148 * Math.sin(M) + 0.02 * Math.sin(2 * M) + 0.0003 * Math.sin(3 * M))
  const L = M + C + RAD * 102.9372 + Math.PI

  return { dec: declination(L, 0), ra: rightAscension(L, 0) }
}

function moonCoords(d) {
  const L = RAD * (218.316 + 13.176396 * d)
  const M = RAD * (134.963 + 13.064993 * d)
  const F = RAD * (93.272 + 13.22935 * d)
  const l = L + RAD * 6.289 * Math.sin(M)
  const b = RAD * 5.128 * Math.sin(F)
  const dist = 385001 - 20905 * Math.cos(M)

  return { ra: rightAscension(l, b), dec: declination(l, b), dist }
}

// {altitudeDeg, azimuthDeg} where azimuthDeg is a compass bearing
// (0 = north, 90 = east, 180 = south, 270 = west).
function horizontal(ms, latitude, longitude, coords) {
  const lw = RAD * -longitude
  const phi = RAD * latitude
  const d = toDays(ms)
  const c = coords(d)
  const H = siderealTime(d, lw) - c.ra

  return {
    altitudeDeg: altitude(H, phi, c.dec) / RAD,
    azimuthDeg: (azimuth(H, phi, c.dec) / RAD + 180 + 360) % 360,
  }
}

// Illuminated fraction (0 = new, 1 = full) and phase (0..1, 0.5 = full,
// < 0.5 waxing).
function moonIllumination(ms) {
  const d = toDays(ms)
  const s = sunCoords(d)
  const m = moonCoords(d)
  const sunDistance = 149598000

  const phi = Math.acos(
    Math.sin(s.dec) * Math.sin(m.dec) +
      Math.cos(s.dec) * Math.cos(m.dec) * Math.cos(s.ra - m.ra)
  )
  const inc = Math.atan2(
    sunDistance * Math.sin(phi),
    m.dist - sunDistance * Math.cos(phi)
  )
  const angle = Math.atan2(
    Math.cos(s.dec) * Math.sin(s.ra - m.ra),
    Math.sin(s.dec) * Math.cos(m.dec) -
      Math.cos(s.dec) * Math.sin(m.dec) * Math.cos(s.ra - m.ra)
  )

  return {
    fraction: (1 + Math.cos(inc)) / 2,
    phase: 0.5 + (0.5 * inc * (angle < 0 ? -1 : 1)) / Math.PI,
  }
}

// Sky palette keyed by the sun's altitude in degrees: [altitude, top RGB,
// horizon RGB, daylight 0..1]. Linearly interpolated between neighbours,
// so the colours drift continuously through golden hour and twilight
// instead of snapping between "day" and "night".
const SKY_STOPS = [
  [-90, [8, 12, 34], [18, 26, 58], 0],
  [-18, [8, 12, 34], [18, 26, 58], 0],
  [-12, [14, 22, 56], [36, 46, 92], 0.04],
  [-6, [34, 42, 96], [134, 84, 118], 0.15],
  [-2, [70, 92, 156], [236, 128, 82], 0.35],
  [0, [92, 122, 180], [252, 158, 84], 0.5],
  [6, [126, 174, 222], [254, 208, 150], 0.8],
  [15, [104, 168, 236], [196, 226, 250], 0.95],
  [90, [72, 142, 232], [186, 220, 250], 1],
]

function lerp(a, b, t) {
  return a + (b - a) * t
}

function lerpRgb(a, b, t) {
  return [
    Math.round(lerp(a[0], b[0], t)),
    Math.round(lerp(a[1], b[1], t)),
    Math.round(lerp(a[2], b[2], t)),
  ]
}

function skyPalette(sunAltitudeDeg) {
  let lower = SKY_STOPS[0]
  let upper = SKY_STOPS[SKY_STOPS.length - 1]

  for (let i = 0; i < SKY_STOPS.length - 1; i++) {
    if (
      sunAltitudeDeg >= SKY_STOPS[i][0] &&
      sunAltitudeDeg <= SKY_STOPS[i + 1][0]
    ) {
      lower = SKY_STOPS[i]
      upper = SKY_STOPS[i + 1]
      break
    }
  }

  const span = upper[0] - lower[0]
  const t = span === 0 ? 0 : (sunAltitudeDeg - lower[0]) / span

  return {
    top: lerpRgb(lower[1], upper[1], t),
    horizon: lerpRgb(lower[2], upper[2], t),
    daylight: lerp(lower[3], upper[3], t),
  }
}

// Where a body sits in the garden viewport. The windows face south (the
// sun crosses the southern sky at Taiwan's latitude), so east is on the
// left and west on the right; `x` is 0..1 across the view and `y` is
// 0 at the horizon, 1 straight overhead. The horizontal spread shrinks
// with altitude (a top-down projection), so a body passing near the
// zenith — the summer noon sun here — drifts through the middle instead
// of jumping from one side to the other as its azimuth flips.
function viewportPosition(body) {
  const altitude = body.altitudeDeg * RAD

  const x = 0.5 - 0.5 * Math.sin(body.azimuthDeg * RAD) * Math.cos(altitude)

  return {
    // Kept a little inside the edges so a disc near due east/west isn't
    // drawn half-clipped by the garden's frame.
    x: Math.min(0.95, Math.max(0.05, x)),
    y: Math.max(0, Math.sin(altitude)),
  }
}

function resolvePhase(sunAltitudeDeg, sunAzimuthDeg) {
  const rising = sunAzimuthDeg < 180

  if (sunAltitudeDeg >= 6) {
    return 'day'
  }

  if (sunAltitudeDeg >= 0) {
    return rising ? 'sunrise' : 'sunset'
  }

  if (sunAltitudeDeg >= -12) {
    return rising ? 'dawn' : 'dusk'
  }

  return 'night'
}

// Scenery colours as [day RGB, night RGB] pairs, blended by daylight and
// then tinted a little towards the horizon colour so distant hills pick up
// the warm haze of a sunset the way real ones do.
const SCENE_COLORS = {
  cloud: [
    [255, 255, 255],
    [64, 74, 108],
  ],
  buildingFar: [
    [156, 176, 202],
    [34, 42, 76],
  ],
  buildingNear: [
    [112, 128, 156],
    [24, 30, 58],
  ],
  // Window glass: pale reflection by day, warm lit rooms at night.
  windowLit: [
    [196, 212, 232],
    [255, 208, 112],
  ],
  windowDim: [
    [184, 198, 220],
    [70, 74, 106],
  ],
  hedge: [
    [84, 146, 96],
    [20, 40, 50],
  ],
  lawnTop: [
    [118, 184, 96],
    [24, 52, 46],
  ],
  lawnBottom: [
    [78, 146, 72],
    [14, 36, 36],
  ],
  path: [
    [226, 210, 172],
    [52, 58, 74],
  ],
  canopy: [
    [72, 148, 82],
    [22, 44, 54],
  ],
  canopyDark: [
    [48, 116, 64],
    [14, 32, 42],
  ],
  pine: [
    [44, 112, 84],
    [14, 38, 48],
  ],
  trunk: [
    [124, 86, 58],
    [40, 34, 38],
  ],
}

const HORIZON_TINT = {
  cloud: 0.35,
  buildingFar: 0.4,
  buildingNear: 0.18,
  windowLit: 0,
  windowDim: 0.3,
  hedge: 0.08,
  lawnTop: 0.08,
  lawnBottom: 0.05,
  path: 0.15,
  canopy: 0.08,
  canopyDark: 0.05,
  pine: 0.08,
  trunk: 0.05,
}

function scenePalette(daylight, horizon) {
  const scene = {}

  for (const [name, [day, night]] of Object.entries(SCENE_COLORS)) {
    scene[name] = lerpRgb(
      lerpRgb(night, day, daylight),
      horizon,
      HORIZON_TINT[name]
    )
  }

  return scene
}

export const SKY_PHASE_LABELS = {
  day: '白天',
  sunrise: '日出',
  sunset: '日落',
  dawn: '黎明',
  dusk: '黃昏',
  night: '夜晚',
}

// Everything the floor map needs to draw the sky at one instant, computed
// once per minute by the study-room component and read from bindings.
export function computeSky(ms, latitude, longitude) {
  const sun = horizontal(ms, latitude, longitude, sunCoords)
  const moon = horizontal(ms, latitude, longitude, moonCoords)
  const illumination = moonIllumination(ms)
  const palette = skyPalette(sun.altitudeDeg)
  const sunPosition = viewportPosition(sun)
  const moonPosition = viewportPosition(moon)

  // Warm glow around the sun while it's low: strongest right at the
  // horizon, gone once it's 20° up or 10° under.
  const sunGlow =
    sun.altitudeDeg > 0
      ? Math.max(0, 1 - sun.altitudeDeg / 20)
      : Math.max(0, 1 + sun.altitudeDeg / 10)

  return {
    phase: resolvePhase(sun.altitudeDeg, sun.azimuthDeg),
    daylight: palette.daylight,
    top: palette.top,
    horizon: palette.horizon,
    mid: lerpRgb(palette.top, palette.horizon, 0.55),
    scene: scenePalette(palette.daylight, palette.horizon),
    sunGlow,
    // Fireflies only come out once it's properly dark.
    fireflyOpacity: Math.min(1, Math.max(0, (0.12 - palette.daylight) / 0.12)),
    // Street lamps and lit windows come on through dusk.
    lampOpacity: Math.min(1, Math.max(0, (0.4 - palette.daylight) / 0.3)),
    // Stars fade in through nautical twilight and are fully out by -12°.
    starOpacity: Math.min(1, Math.max(0, (-sun.altitudeDeg - 4) / 8)),
    sunAltitudeDeg: sun.altitudeDeg,
    sunAzimuthDeg: sun.azimuthDeg,
    sunVisible: sun.altitudeDeg > -1,
    sunX: sunPosition.x,
    sunY: sunPosition.y,
    moonAltitudeDeg: moon.altitudeDeg,
    moonAzimuthDeg: moon.azimuthDeg,
    moonVisible: moon.altitudeDeg > 0,
    moonX: moonPosition.x,
    moonY: moonPosition.y,
    moonFraction: illumination.fraction,
    moonPhase: illumination.phase,
  }
}

// A fixed, deterministic star field so the night sky doesn't reshuffle on
// every render. Positions are percentages of the sky area.
export function buildStarField(count = 28) {
  const stars = []
  let seed = 7

  const next = () => {
    seed = (seed * 9301 + 49297) % 233280
    return seed / 233280
  }

  for (let i = 0; i < count; i++) {
    stars.push({
      id: i,
      left: Math.round(next() * 98),
      top: Math.round(next() * 55),
      size: next() > 0.7 ? 2 : 1,
      twinkleDelay: Math.round(next() * 40) / 10,
    })
  }

  return stars
}
