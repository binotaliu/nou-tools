<script setup>
// A chair around a shared table. `backrest` picks which edge the backrest
// sits on so chairs face the table from both sides; `timerSide` mirrors
// that for the always-reserved timer line below the chair.
//
// Table chairs are too small to show nickname/activity inline, so those
// live in a hover/focus/tap popover (peek/unpeek/isPeeking in useSeatGrid).
import { onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  seat: { type: Object, required: true },
  backrest: { type: String, required: true },
  timerSide: { type: String, required: true },
  grid: { type: Object, required: true },
  timer: { type: Object, required: true },
  // The floor's roving tabindex (useSeatRovingFocus): 0 for the one seat
  // that holds the floor's Tab stop, -1 for the rest.
  rovingTabindex: { type: Number, default: 0 },
})

const rootEl = ref(null)

function handleDocumentClick(event) {
  if (rootEl.value && !rootEl.value.contains(event.target)) {
    props.grid.unpeek(props.seat)
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick)
})
</script>

<template>
  <div
    ref="rootEl"
    class="relative flex items-center gap-0.5"
    :class="timerSide === 'top' ? 'flex-col-reverse' : 'flex-col'"
    @mouseenter="grid.peek(seat)"
    @mouseleave="grid.unpeek(seat)"
  >
    <button
      type="button"
      :aria-disabled="grid.isSeatActionable(seat) ? null : 'true'"
      :tabindex="rovingTabindex"
      :class="grid.seatClasses(seat, 'table') + ' ' + backrest"
      :data-testid="grid.seatTestId(seat)"
      :data-seat-code="seat.code"
      :aria-label="grid.seatAriaLabel(seat, timer.spokenTimerLabel(seat))"
      @click="grid.activateSeat(seat)"
      @focus="grid.peek(seat)"
      @blur="grid.unpeek(seat)"
    >
      <span
        v-if="!seat.isOccupied"
        class="text-[0.625rem] font-medium text-theme-700 dark:text-zinc-400"
        >{{ seat.seatNumber }}</span
      >
      <span v-else class="text-base leading-none">{{ seat.emoji }}</span>
      <span
        v-show="grid.isMine(seat)"
        class="absolute -top-1.5 -right-2 rounded-full bg-amber-500 px-1 text-[0.5625rem] leading-4 font-semibold text-white shadow-sm"
        >你</span
      >
    </button>

    <!-- Always rendered (not v-show'd out entirely) so the chair's height is
    reserved whether or not the seat is occupied. -->
    <span
      class="font-mono text-[0.5625rem] tabular-nums"
      :class="seat.isOccupied ? grid.seatTimerLabelClass(seat) : 'invisible'"
      :data-testid="grid.seatTestId(seat) + '-timer'"
      >{{ seat.isOccupied ? timer.timerLabel(seat) : '00:00' }}</span
    >

    <!-- Visual only: the button's name already says all of this. -->
    <div
      v-show="grid.isPeeking(seat)"
      aria-hidden="true"
      class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 w-36 -translate-x-1/2 rounded-lg border border-theme-200 bg-white p-2 text-left shadow-md dark:border-zinc-600 dark:bg-zinc-800"
      :data-testid="grid.seatTestId(seat) + '-popover'"
    >
      <p
        class="flex items-center gap-1.5 text-xs font-semibold text-theme-900 dark:text-zinc-100"
      >
        <span>{{ seat.emoji }}</span>
        <span class="truncate">{{ seat.nickname }}</span>
      </p>
      <p
        class="mt-1 text-[0.6875rem] leading-snug text-theme-700 dark:text-zinc-300"
      >
        {{ grid.thoughtBubbleText(seat) }}
      </p>
      <p
        class="mt-1 font-mono text-[0.6875rem] text-theme-700 tabular-nums dark:text-zinc-400"
      >
        剩餘 {{ timer.timerLabel(seat) }}
      </p>
      <span
        class="absolute top-full left-1/2 size-2 -translate-x-1/2 -translate-y-1/2 rotate-45 border-r border-b border-theme-200 bg-white dark:border-zinc-600 dark:bg-zinc-800"
        aria-hidden="true"
      ></span>
    </div>
  </div>
</template>
