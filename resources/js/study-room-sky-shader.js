// WebGL sky for the 自習室 garden. The CSS gradient underneath it (see
// gardenSkyStyle in study-room.js) stays the fallback: if there's no WebGL
// context, or the shader fails to build, createSkyRenderer returns null and
// the garden keeps the plain three-stop gradient it has always had.
//
// The shader draws the sky and nothing else. Everything in front of it —
// the drifting clouds, the skyline, the trees, the sun and moon discs — is
// still the DOM and SVG scenery it always was, flat-filled to match.
//
// It doesn't invent its own colours either. It takes the same palette the
// rest of the scene is tinted from (top/mid/horizon, from SKY_STOPS) and
// reproduces that gradient exactly, then adds the things a three-stop
// gradient can't express:
//
//   - Perez sky luminance, so the sky brightens towards the horizon and
//     around the sun the way a real one does instead of interpolating
//     linearly from top to bottom;
//   - a halo around the sun, and a smaller, cooler one around the moon,
//     with a proper angular falloff;
//   - a dither, because a smooth gradient across ~1100px of an 8-bit
//     framebuffer bands visibly, and banding is what makes a sky read as
//     vector art.
//
// All of it is driven by the sun/moon snapshot computeSky() already
// produces, so the sky follows the real one over Taiwan exactly as the
// rest of the garden does. Nothing here animates: the snapshot changes
// once a minute, and the canvas is redrawn when it does.

const VERTEX_SHADER = `
attribute vec2 aPosition;
varying vec2 vUv;

void main() {
  vUv = aPosition * 0.5 + 0.5;
  gl_Position = vec4(aPosition, 0.0, 1.0);
}
`

const FRAGMENT_SHADER = `
#ifdef GL_FRAGMENT_PRECISION_HIGH
precision highp float;
#else
precision mediump float;
#endif

uniform float uAspect;
uniform vec3 uTopColor;
uniform vec3 uMidColor;
uniform vec3 uHorizonColor;
// Sun and moon in the same viewport coordinates the DOM discs are placed
// at: x across the view, y measured downwards from the top edge.
uniform vec2 uSun;
uniform vec2 uMoon;
uniform float uSunGlow;
uniform float uSunStrength;
uniform float uMoonGlow;
uniform float uDaylight;

varying vec2 vUv;

const float PI = 3.141592653589793;
const float HALF_PI = 1.5707963267948966;

// Where the ground meets the sky in the garden, and where a body drawn
// straight overhead sits — both measured from the top edge. Kept in step
// with bodyPoint() in study-room.js.
const float HORIZON_Y = 0.42;
const float ZENITH_Y = 0.02;

// Perez distribution coefficients for luminance at turbidity 3 — a clear
// but not bone-dry day, which is about right for the Taipei basin.
const float PEREZ_A = -0.9269;
const float PEREZ_B = -0.6387;
const float PEREZ_C = 5.2570;
const float PEREZ_D = -2.2153;
const float PEREZ_E = 0.1693;

// Highlights roll off above this instead of clipping flat.
const float SOFT_KNEE = 0.75;

// Per-channel highlight rolloff. A hard clip is exactly what turns the
// bright sky around the sun into a flat white paper cut-out; rolling it
// off towards white keeps the gradient going all the way in.
vec3 softClip(vec3 c) {
  vec3 over = max(c - SOFT_KNEE, 0.0);

  return min(c, vec3(SOFT_KNEE)) +
    (1.0 - SOFT_KNEE) * (1.0 - exp(-over / (1.0 - SOFT_KNEE)));
}

// The two halves of the Perez distribution, each normalised so it sits at
// 1.0 in its neutral case. Kept apart on purpose: multiplied together and
// normalised at the zenith, the whole sky would brighten and darken as the
// sun moved, which would fight the palette instead of adding to it. Apart,
// each one only describes *variation*, and the palette keeps deciding how
// bright and what colour the sky is.

// How much brighter the sky gets looking down towards the horizon.
// 1.0 at the zenith, roughly 2x at the horizon.
float perezGradient(float cosTheta) {
  float ct = max(cosTheta, 0.045);

  return (1.0 + PEREZ_A * exp(PEREZ_B / ct)) /
    (1.0 + PEREZ_A * exp(PEREZ_B));
}

// The broad brightening wrapped around the sun. 1.0 at a right angle to
// it, climbing steeply as you look towards it.
float perezCircumsolar(float gamma) {
  float cg = cos(gamma);

  return (1.0 + PEREZ_C * exp(PEREZ_D * gamma) + PEREZ_E * cg * cg) /
    (1.0 + PEREZ_C * exp(PEREZ_D * HALF_PI));
}

float hash(vec2 p) {
  vec3 q = fract(vec3(p.xyx) * 0.1031);
  q += dot(q, q.yzx + 33.33);

  return fract((q.x + q.y) * q.z);
}

// Angle between a viewport point and a body, treating the view as ~180°
// wide and horizon-to-zenith as 90° tall. Strongly anisotropic on screen:
// the garden squeezes 90° of elevation into a tenth of the width it gives
// 180° of azimuth, so equal angles are nothing like equal distances.
float angleTo(vec2 point, vec2 body) {
  vec2 delta = vec2(
    (point.x - body.x) * PI,
    (point.y - body.y) / (HORIZON_Y - ZENITH_Y) * HALF_PI
  );

  return min(length(delta), PI);
}

// Distance from a body in units of the garden's height. Haloes are drawn
// in this rather than in angle: a circular halo in angle lands on screen
// as a thin horizontal streak, and the sun and moon discs drawn over the
// canvas are round. The broad, genuinely wide brightening around the sun
// is the Perez term's job, and that one does use the angle.
float discDistance(vec2 point, vec2 body) {
  return length(vec2((point.x - body.x) * uAspect, point.y - body.y));
}

void main() {
  // y downwards from the top edge, matching how the scene is laid out.
  float yTop = 1.0 - vUv.y;
  vec2 point = vec2(vUv.x, yTop);

  // The palette gradient, reproduced stop for stop so the shader sky and
  // the CSS fallback are the same colours.
  vec3 base = yTop < 0.55
    ? mix(uTopColor, uMidColor, yTop / 0.55)
    : mix(uMidColor, uHorizonColor, (yTop - 0.55) / 0.45);

  float elevation = clamp(
    (HORIZON_Y - yTop) / (HORIZON_Y - ZENITH_Y),
    0.0,
    1.0
  );
  float sunElevation = clamp(
    (HORIZON_Y - uSun.y) / (HORIZON_Y - ZENITH_Y),
    0.0,
    1.0
  );

  float gamma = angleTo(point, uSun);

  // The palette's own top-to-horizon ramp already carries most of the
  // vertical brightening, so Perez's gradient term is applied gently and
  // measured from mid-sky rather than the zenith — it's here to bend the
  // ramp into the right shape, not to widen its range. The circumsolar
  // term has no counterpart in the palette at all, so it comes through
  // much more strongly.
  float gradient = perezGradient(sin(elevation * HALF_PI)) /
    perezGradient(0.70710678);
  float circumsolar = mix(1.0, perezCircumsolar(gamma), uSunStrength);

  // Perez only describes a daylit sky; through dusk it hands over to the
  // palette, which already knows what twilight looks like.
  float atmosphere = smoothstep(0.0, 0.35, uDaylight);
  vec3 color = base * mix(
    1.0,
    pow(clamp(gradient, 0.4, 2.0), 0.15) *
      pow(clamp(circumsolar, 0.4, 6.0), 0.20),
    atmosphere
  );

  // Haze around the sun: a wide lobe plus a tight one, reddening as it
  // drops towards the horizon. Deliberately restrained — the sun disc
  // itself is a DOM element with its own bloom drawn over this.
  float sunDistance = discDistance(point, uSun);
  float glow = exp(-sunDistance * 1.2) * 0.26 + exp(-sunDistance * 4.0) * 0.30;
  vec3 glowColor = mix(
    vec3(1.0, 0.88, 0.66),
    vec3(1.0, 0.56, 0.30),
    1.0 - sunElevation
  );
  color += glowColor * glow * (0.20 + 0.80 * uSunGlow) * uSunStrength;

  // A far cooler, far smaller version of the same thing for the moon.
  color += vec3(0.72, 0.80, 1.0) *
    exp(-discDistance(point, uMoon) * 5.5) * 0.22 * uMoonGlow;

  color = softClip(color);

  // Break up the 8-bit quantisation of a very wide, very smooth gradient.
  color += (hash(gl_FragCoord.xy) - 0.5) / 255.0;

  gl_FragColor = vec4(clamp(color, 0.0, 1.0), 1.0);
}
`

const UNIFORM_NAMES = [
  'uAspect',
  'uTopColor',
  'uMidColor',
  'uHorizonColor',
  'uSun',
  'uMoon',
  'uSunGlow',
  'uSunStrength',
  'uMoonGlow',
  'uDaylight',
]

// A cap, not the real device ratio: the sky is all soft gradients and the
// dither hides the rest, so rendering it at full retina resolution buys
// nothing but battery.
const MAX_PIXEL_RATIO = 1.5

function compile(gl, type, source) {
  const shader = gl.createShader(type)

  gl.shaderSource(shader, source)
  gl.compileShader(shader)

  if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
    gl.deleteShader(shader)

    return null
  }

  return shader
}

function buildProgram(gl) {
  const vertex = compile(gl, gl.VERTEX_SHADER, VERTEX_SHADER)
  const fragment = compile(gl, gl.FRAGMENT_SHADER, FRAGMENT_SHADER)

  if (!vertex || !fragment) {
    return null
  }

  const program = gl.createProgram()

  gl.attachShader(program, vertex)
  gl.attachShader(program, fragment)
  gl.linkProgram(program)
  gl.deleteShader(vertex)
  gl.deleteShader(fragment)

  if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
    gl.deleteProgram(program)

    return null
  }

  return program
}

/**
 * @param {HTMLCanvasElement} canvas
 * @param {{onFirstPaint: function, onContextLost: function}} [callbacks]
 *        onFirstPaint fires once the canvas has actually been painted, and
 *        onContextLost if the context goes away later. The context is
 *        opaque, so the canvas has to stay hidden outside that window or it
 *        covers the CSS sky with a black rectangle — which is exactly what
 *        would happen when it mounts at zero size, behind the profile modal.
 * @return {{update: function, sample: function, destroy: function}|null}
 *         null when WebGL isn't usable, so the caller can keep the CSS sky.
 */
export function createSkyRenderer(canvas, callbacks = {}) {
  const options = {
    alpha: false,
    antialias: false,
    depth: false,
    stencil: false,
    powerPreference: 'low-power',
  }

  let gl = null

  try {
    gl =
      canvas.getContext('webgl', options) ||
      canvas.getContext('experimental-webgl', options)
  } catch (error) {
    return null
  }

  if (!gl) {
    return null
  }

  const program = buildProgram(gl)

  if (!program) {
    return null
  }

  const uniforms = {}

  for (const name of UNIFORM_NAMES) {
    uniforms[name] = gl.getUniformLocation(program, name)
  }

  const buffer = gl.createBuffer()

  gl.bindBuffer(gl.ARRAY_BUFFER, buffer)
  gl.bufferData(
    gl.ARRAY_BUFFER,
    new Float32Array([-1, -1, 3, -1, -1, 3]),
    gl.STATIC_DRAW
  )

  const position = gl.getAttribLocation(program, 'aPosition')

  gl.useProgram(program)
  gl.enableVertexAttribArray(position)
  gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0)

  // Whatever the last update() handed us, held so a resize can redraw
  // without the component pushing again.
  let sky = null
  let painted = false
  let lost = false

  const resize = () => {
    const ratio = Math.min(window.devicePixelRatio || 1, MAX_PIXEL_RATIO)
    const width = Math.round(canvas.clientWidth * ratio)
    const height = Math.round(canvas.clientHeight * ratio)

    if (width < 1 || height < 1) {
      return false
    }

    if (canvas.width !== width || canvas.height !== height) {
      canvas.width = width
      canvas.height = height
    }

    return true
  }

  const draw = () => {
    if (lost || !sky || !resize()) {
      return false
    }

    gl.viewport(0, 0, canvas.width, canvas.height)
    gl.useProgram(program)

    gl.uniform1f(uniforms.uAspect, canvas.width / canvas.height)
    gl.uniform3fv(uniforms.uTopColor, sky.top)
    gl.uniform3fv(uniforms.uMidColor, sky.mid)
    gl.uniform3fv(uniforms.uHorizonColor, sky.horizon)
    gl.uniform2f(uniforms.uSun, sky.sun[0], sky.sun[1])
    gl.uniform2f(uniforms.uMoon, sky.moon[0], sky.moon[1])
    gl.uniform1f(uniforms.uSunGlow, sky.sunGlow)
    gl.uniform1f(uniforms.uSunStrength, sky.sunStrength)
    gl.uniform1f(uniforms.uMoonGlow, sky.moonGlow)
    gl.uniform1f(uniforms.uDaylight, sky.daylight)

    gl.drawArrays(gl.TRIANGLES, 0, 3)

    if (!painted) {
      painted = true

      if (typeof callbacks.onFirstPaint === 'function') {
        callbacks.onFirstPaint()
      }
    }

    return true
  }

  const resizeObserver =
    typeof ResizeObserver === 'function'
      ? new ResizeObserver(() => {
          draw()
        })
      : null

  if (resizeObserver) {
    resizeObserver.observe(canvas)
  }

  // A lost context leaves the canvas blank, so hand the sky back to the
  // CSS gradient rather than leaving a hole where it was.
  const onContextLost = event => {
    event.preventDefault()
    lost = true

    if (typeof callbacks.onContextLost === 'function') {
      callbacks.onContextLost()
    }
  }

  canvas.addEventListener('webglcontextlost', onContextLost)

  return {
    /**
     * @param {object} next Shader inputs built by skyShaderInputs().
     */
    update(next) {
      sky = next
      draw()
    },

    /**
     * Colour currently on screen at a point in the canvas, as 0-255 RGB.
     * Reads back straight after a draw in the same task, which is what
     * makes it valid without preserveDrawingBuffer. For tests.
     *
     * @param {number} u
     * @param {number} v 0 at the top edge.
     * @return {number[]|null}
     */
    sample(u, v) {
      if (!draw()) {
        return null
      }

      const pixel = new Uint8Array(4)

      gl.readPixels(
        Math.min(canvas.width - 1, Math.max(0, Math.round(u * canvas.width))),
        Math.min(
          canvas.height - 1,
          Math.max(0, Math.round((1 - v) * canvas.height))
        ),
        1,
        1,
        gl.RGBA,
        gl.UNSIGNED_BYTE,
        pixel
      )

      return [pixel[0], pixel[1], pixel[2]]
    },

    destroy() {
      canvas.removeEventListener('webglcontextlost', onContextLost)

      if (resizeObserver) {
        resizeObserver.disconnect()
      }

      gl.deleteProgram(program)
      gl.deleteBuffer(buffer)

      const loseContext = gl.getExtension('WEBGL_lose_context')

      if (loseContext) {
        loseContext.loseContext()
      }
    },
  }
}
