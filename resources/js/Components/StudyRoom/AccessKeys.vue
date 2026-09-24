<script setup>
// The study room's page-scoped accesskeys (digits 4–8, see AGENTS.md and
// the /accessibility page). The controls they stand for swap in and out
// with v-show or sit in a panel that can be minimised, and a hidden element
// cannot take an accesskey, so each key lives on one of these off-screen
// buttons instead, like AppLayout's key 2: out of the tab order and hidden
// from assistive tech, since every real control is reachable on its own.
//
// Key 4 (快速入座) is on the real toolbar button while you are not seated;
// here it only takes over once you are.
import { nextTick } from 'vue'

const props = defineProps({
  socket: { type: Object, required: true },
  timer: { type: Object, required: true },
  roving: { type: Object, required: true },
  announcer: { type: Object, required: true },
})

function visible(testId) {
  const element = document.querySelector('[data-testid="' + testId + '"]')

  return element && element.getClientRects().length > 0 ? element : null
}

function focusControlPanel() {
  const target =
    visible('study-room-banner-expand') ||
    document.querySelector('[data-testid="study-room-control-panel-heading"]')

  target?.focus()
}

// 5: whichever of start / pause / resume / next round applies, then focus
// lands on the control that replaced it, as the panel's own buttons do.
async function toggleTimer() {
  const { timer } = props
  let next

  if (!timer.hasTimer()) {
    await timer.startTimer()
    next = 'study-room-pause-timer'
  } else if (timer.isPaused()) {
    await timer.resumeTimer()
    next = 'study-room-pause-timer'
  } else if (timer.canPause()) {
    await timer.pauseTimer()
    next = 'study-room-resume-timer'
  } else if (timer.canStartNextRound()) {
    await timer.startNextRound()
    next = 'study-room-pause-timer'
  }

  await nextTick()

  const target = next ? visible(next) : null

  if (target) {
    target.focus()
  } else {
    focusControlPanel()
  }
}

// 6: your own seat if you have one, else the first floor's current seat;
// in the list view, the matching row.
function focusSeats() {
  const { socket, roving } = props
  const floor = socket.state?.floors[0]
  const code =
    socket.heldSeatCode || (floor ? roving.activeCodeFor(floor) : null)

  if (!code) {
    return
  }

  roving.focusSeat(code)
}

// 7: the status in one sentence, spoken; focus goes to the panel so the
// next key press continues from there rather than from this hidden button.
function readStatus() {
  props.announcer.say(props.timer.statusSentence())
  focusControlPanel()
}

function openFocusMode() {
  props.timer.openFocusMode()
}
</script>

<template>
  <div class="sr-only" aria-hidden="true" data-testid="study-room-accesskeys">
    <template v-if="socket.heldSeatCode">
      <button
        type="button"
        accesskey="4"
        tabindex="-1"
        data-testid="study-room-accesskey-panel"
        @click="focusControlPanel()"
      >
        控制列
      </button>
      <button
        type="button"
        accesskey="5"
        tabindex="-1"
        data-testid="study-room-accesskey-timer"
        @click="toggleTimer()"
      >
        開始或暫停計時
      </button>
      <button
        type="button"
        accesskey="7"
        tabindex="-1"
        data-testid="study-room-accesskey-status"
        @click="readStatus()"
      >
        朗讀目前狀態
      </button>
      <button
        v-if="timer.hasTimer()"
        type="button"
        accesskey="8"
        tabindex="-1"
        data-testid="study-room-accesskey-focus-mode"
        @click="openFocusMode()"
      >
        全螢幕專注
      </button>
    </template>
    <button
      v-if="socket.state"
      type="button"
      accesskey="6"
      tabindex="-1"
      data-testid="study-room-accesskey-seats"
      @click="focusSeats()"
    >
      座位表
    </button>
  </div>
</template>
