<script setup>
// Above the seats: 快速入座 (take the first free seat without hunting for
// one), and the 平面圖 / 清單 switch. The list view reads the same room as a
// table, for screen readers and anyone who'd rather scan than look.
import {
  ListBulletIcon,
  MapIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  socket: { type: Object, required: true },
  grid: { type: Object, required: true },
  announcer: { type: Object, required: true },
  view: { type: String, required: true },
})

const emit = defineEmits(['update:view'])

function takeFirstFreeSeat() {
  if (props.socket.busySeatCode !== null) {
    return
  }

  const seat = props.grid.firstFreeSeat()

  if (!seat) {
    props.announcer.say('目前沒有空位', { assertive: true })
    return
  }

  props.socket.take(seat.code)
}

const viewButtonClass =
  'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition aria-pressed:bg-theme-700 aria-pressed:text-white dark:aria-pressed:bg-theme-500 dark:aria-pressed:text-zinc-950'
</script>

<template>
  <div
    class="flex flex-wrap items-center justify-between gap-3"
    data-testid="study-room-toolbar"
  >
    <button
      v-if="!socket.heldSeatCode"
      type="button"
      :aria-disabled="socket.busySeatCode !== null ? 'true' : null"
      class="inline-flex items-center gap-1.5 rounded-lg bg-theme-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-theme-800 aria-disabled:opacity-50 dark:bg-theme-500 dark:text-zinc-950 dark:hover:bg-theme-400"
      data-testid="study-room-quick-seat"
      @click="takeFirstFreeSeat()"
    >
      <SparklesIcon class="size-4" />
      快速入座
    </button>
    <span v-else></span>

    <div
      role="group"
      aria-label="座位顯示方式"
      class="inline-flex rounded-lg border border-theme-200 bg-white p-0.5 dark:border-zinc-700 dark:bg-zinc-900"
    >
      <button
        type="button"
        :aria-pressed="view === 'map' ? 'true' : 'false'"
        :class="viewButtonClass"
        data-testid="study-room-view-map"
        @click="emit('update:view', 'map')"
      >
        <MapIcon class="size-4" />
        平面圖
      </button>
      <button
        type="button"
        :aria-pressed="view === 'list' ? 'true' : 'false'"
        :class="viewButtonClass"
        data-testid="study-room-view-list"
        @click="emit('update:view', 'list')"
      >
        <ListBulletIcon class="size-4" />
        清單
      </button>
    </div>
  </div>
</template>
