<script setup>
// One café seat drawn as a piece of furniture: a round stool at the counter
// or a chair whose backrest sits on the edge that faces away from its table
// (`backrest`), the same trick the study room's TableChair uses. Like those
// seats it is `aria-disabled` rather than `disabled` when it can't be taken,
// so focus never drops and the occupant stays readable.
import { computed } from 'vue'

const props = defineProps({
  seat: { type: Object, required: true },
  socket: { type: Object, required: true },
  canSit: { type: Boolean, required: true },
  rovingTabindex: { type: Number, default: -1 },
  // `stool` sits at the counter, `chair` at a table.
  variant: { type: String, default: 'chair' },
  // Which edge of a chair carries the backrest: top, bottom, left or right.
  backrest: { type: String, default: 'bottom' },
  // Reactive "now" (ms) so a cup disappears when its coffee runs out.
  now: { type: Number, default: () => Date.now() },
})

const emit = defineEmits(['activate'])

const mine = computed(() => props.seat.isYou)

const coffee = computed(() =>
  props.seat.coffee &&
  new Date(props.seat.coffee.expiresAt).getTime() > props.now
    ? props.seat.coffee
    : null
)

const actionable = computed(
  () =>
    mine.value ||
    (!props.seat.isOccupied &&
      props.socket.heldSeatCode.value === null &&
      props.socket.busySeatCode.value === null)
)

const ariaLabel = computed(() => {
  if (mine.value) {
    return `${props.seat.label}，你坐在這裡`
  }

  if (props.seat.isOccupied) {
    return `${props.seat.label}，${props.seat.nickname ?? '有人'}坐在這裡${coffee.value ? `，桌上有一杯${coffee.value.name}` : ''}`
  }

  return `${props.seat.label}，空位${props.canSit ? '，按下入座' : ''}`
})

const BACKRESTS = {
  top: 'border-t-[5px]',
  bottom: 'border-b-[5px]',
  left: 'border-l-[5px]',
  right: 'border-r-[5px]',
}

const shapeClasses = computed(() => {
  if (props.variant === 'stool') {
    return 'size-11 rounded-full border-2 border-b-4'
  }

  return `size-11 rounded-lg border-2 ${BACKRESTS[props.backrest] ?? BACKRESTS.bottom}`
})

const stateClasses = computed(() => {
  if (mine.value) {
    return 'border-amber-400 bg-amber-50 shadow-sm dark:border-amber-600 dark:bg-amber-950/40'
  }

  if (props.seat.isOccupied) {
    return 'border-theme-400 bg-white shadow-sm dark:border-zinc-500 dark:bg-zinc-800'
  }

  return 'border-theme-300 bg-white/70 hover:border-theme-400 hover:bg-white dark:border-zinc-600 dark:bg-zinc-800/60 dark:hover:border-zinc-500 dark:hover:bg-zinc-800'
})
</script>

<template>
  <button
    type="button"
    :aria-disabled="actionable ? null : 'true'"
    :tabindex="rovingTabindex"
    :aria-label="ariaLabel"
    :data-seat-code="seat.code"
    :data-testid="`cafe-seat-${seat.code}`"
    class="group relative flex w-14 flex-col items-center gap-0.5 text-center"
    :class="
      socket.busySeatCode.value === seat.code
        ? 'cursor-wait opacity-50'
        : actionable && !mine
          ? 'cursor-pointer'
          : 'cursor-default'
    "
    @click="emit('activate', seat)"
  >
    <span
      class="relative flex items-center justify-center transition"
      :class="[
        shapeClasses,
        stateClasses,
        actionable && !mine ? 'group-active:translate-y-px' : '',
      ]"
      aria-hidden="true"
    >
      <span v-if="seat.isOccupied" class="text-lg leading-none">{{
        seat.emoji
      }}</span>
      <span
        v-else
        class="text-[0.625rem] font-medium text-theme-700 dark:text-zinc-400"
        >{{ seat.seatNumber }}</span
      >
      <span
        v-if="coffee"
        class="absolute -top-2 -right-2 text-sm leading-none drop-shadow-sm"
        :data-testid="`cafe-seat-${seat.code}-coffee`"
        >{{ coffee.emoji }}</span
      >
      <span
        v-show="mine"
        class="absolute -top-1.5 -left-2 rounded-full bg-amber-700 px-1 text-[0.5625rem] leading-4 font-semibold text-white shadow-sm"
        >你</span
      >
    </span>
    <span
      class="max-w-full truncate text-[0.625rem] leading-tight font-medium"
      :class="
        seat.isOccupied
          ? 'text-theme-900 dark:text-zinc-100'
          : 'text-theme-600 opacity-0 transition group-hover:opacity-100 dark:text-zinc-400'
      "
      aria-hidden="true"
    >
      {{ seat.isOccupied ? seat.nickname : canSit ? '入座' : '空位' }}
    </span>
  </button>
</template>
