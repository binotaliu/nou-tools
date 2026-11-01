<script setup>
// One café seat: a stool, window chair or bench slot. Like the study room's
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
</script>

<template>
  <button
    type="button"
    :aria-disabled="actionable ? null : 'true'"
    :tabindex="rovingTabindex"
    :aria-label="ariaLabel"
    :data-seat-code="seat.code"
    :data-testid="`cafe-seat-${seat.code}`"
    class="group relative flex w-full min-w-14 flex-col items-center gap-0.5 rounded-xl border-2 border-b-4 px-1 pt-1.5 pb-1 text-center transition"
    :class="[
      mine
        ? 'border-amber-400 bg-amber-50 dark:border-amber-600 dark:bg-amber-950/40'
        : seat.isOccupied
          ? 'border-theme-300 bg-theme-100 dark:border-zinc-600 dark:bg-zinc-800'
          : 'border-theme-200 bg-white hover:border-theme-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500',
      actionable && !mine
        ? 'cursor-pointer active:translate-y-px active:border-b-2'
        : 'cursor-default',
    ]"
    @click="emit('activate', seat)"
  >
    <span class="text-xl leading-none" aria-hidden="true">
      <template v-if="seat.isOccupied">{{ seat.emoji }}</template>
      <svg
        v-else
        viewBox="0 0 24 24"
        class="size-5 text-theme-300 dark:text-zinc-600"
        fill="currentColor"
      >
        <template v-if="variant === 'stool'">
          <ellipse cx="12" cy="8" rx="7" ry="3" />
          <rect x="11" y="9" width="2" height="10" />
          <rect x="6" y="19" width="12" height="2" rx="1" />
        </template>
        <template v-else>
          <rect x="6" y="3" width="12" height="9" rx="2" />
          <rect x="5" y="12" width="14" height="4" rx="1.5" />
          <rect x="6" y="16" width="2" height="5" />
          <rect x="16" y="16" width="2" height="5" />
        </template>
      </svg>
    </span>
    <span
      class="max-w-full truncate text-[0.6875rem] font-medium"
      :class="
        seat.isOccupied
          ? 'text-theme-900 dark:text-zinc-100'
          : 'text-theme-600 dark:text-zinc-400'
      "
      aria-hidden="true"
    >
      {{ seat.isOccupied ? seat.nickname : '空位' }}
    </span>
    <span
      v-if="coffee"
      class="absolute -bottom-2 -left-1 text-base leading-none"
      aria-hidden="true"
      :data-testid="`cafe-seat-${seat.code}-coffee`"
      >{{ coffee.emoji }}</span
    >
    <span
      v-if="mine"
      class="absolute -top-2 -right-1 rounded-full bg-amber-400 px-1.5 text-[0.625rem] font-bold text-amber-950"
      aria-hidden="true"
    >
      你
    </span>
  </button>
</template>
