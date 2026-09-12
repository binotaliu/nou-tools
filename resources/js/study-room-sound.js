// A short two-note chime played when the viewer's own timer runs out. One
// Audio element is reused across plays (currentTime reset to 0) rather than
// constructed fresh each time, so back-to-back finishes (focus ending right
// as a break starts, say) don't pile up separate HTMLAudioElements.
let audio = null

export function playTimerFinishedSound() {
  if (!audio) {
    audio = new Audio('/sounds/timer-done.wav')
  }

  audio.currentTime = 0
  // Browsers reject play() when it runs without a recent user gesture —
  // that's expected here on a fresh tab load and not worth surfacing.
  audio.play().catch(() => {})
}
