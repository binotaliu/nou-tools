<script setup>
// The room as tables, one per floor: seat, who, what they're doing, and the
// time on their timer, plus 入座 for free seats. The same socket, grid and
// timer as the floor map, so the preview (useStudyRoomDemo) works here too.
//
// Each row's header cell carries data-seat-code (tabindex -1), so the
// page's "return focus to the seat" (useSeatRovingFocus.focusSeat) lands on
// the right row in this view as well.
import { ref } from 'vue'

defineProps({
  socket: { type: Object, required: true },
  grid: { type: Object, required: true },
  timer: { type: Object, required: true },
})

const FILTERS = [
  { value: 'all', label: '全部' },
  { value: 'occupied', label: '有人' },
  { value: 'free', label: '空位' },
]

const filter = ref('all')

function floorSeats(floor) {
  return [...floor.soloSeats, ...floor.tables.flatMap(table => table.seats)]
}

function visibleSeats(floor) {
  return floorSeats(floor).filter(seat => {
    if (filter.value === 'occupied') {
      return seat.isOccupied
    }

    if (filter.value === 'free') {
      return !seat.isOccupied
    }

    return true
  })
}
</script>

<template>
  <div class="space-y-4" data-testid="study-room-seat-list">
    <div
      role="group"
      aria-label="篩選座位"
      class="flex flex-wrap gap-2"
      data-testid="study-room-seat-list-filters"
    >
      <button
        v-for="option in FILTERS"
        :key="option.value"
        type="button"
        :aria-pressed="filter === option.value ? 'true' : 'false'"
        class="rounded-full border border-theme-200 bg-white px-3 py-1 text-sm text-theme-800 transition hover:border-theme-400 aria-pressed:border-theme-700 aria-pressed:bg-theme-700 aria-pressed:text-white dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:aria-pressed:border-theme-500 dark:aria-pressed:bg-theme-500 dark:aria-pressed:text-zinc-950"
        :data-testid="'study-room-seat-list-filter-' + option.value"
        @click="filter = option.value"
      >
        {{ option.label }}
      </button>
    </div>

    <div
      v-for="floor in socket.state ? socket.state.floors : []"
      :key="floor.floor"
      class="overflow-x-auto rounded-xl border border-theme-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
      :data-testid="'study-room-list-floor-' + floor.floor"
    >
      <table class="w-full text-left text-sm">
        <caption
          class="px-4 pt-3 pb-2 text-left text-base font-semibold text-theme-900 dark:text-zinc-100"
        >
          {{
            floor.label
          }}閱覽室（{{
            floor.occupiedCount
          }}
          /
          {{
            floor.totalCount
          }}
          人在座）
          <span
            v-if="grid.stairSpokenHint(floor)"
            class="block text-xs font-normal text-theme-700 dark:text-zinc-400"
            >{{ grid.stairSpokenHint(floor) }}</span
          >
        </caption>
        <thead
          class="border-y border-theme-100 text-xs text-theme-700 dark:border-zinc-800 dark:text-zinc-400"
        >
          <tr>
            <th scope="col" class="px-4 py-2 font-medium">座位</th>
            <th scope="col" class="px-4 py-2 font-medium">同學</th>
            <th scope="col" class="px-4 py-2 font-medium">在做什麼</th>
            <th scope="col" class="px-4 py-2 font-medium">計時</th>
            <th scope="col" class="px-4 py-2 font-medium">
              <span class="sr-only">動作</span>
            </th>
          </tr>
        </thead>
        <tbody class="text-theme-900 dark:text-zinc-100">
          <tr v-if="visibleSeats(floor).length === 0">
            <td colspan="5" class="px-4 py-3 text-theme-700 dark:text-zinc-400">
              這層沒有符合的座位
            </td>
          </tr>
          <tr
            v-for="seat in visibleSeats(floor)"
            :key="seat.code"
            class="border-t border-theme-100 dark:border-zinc-800"
            :class="grid.isMine(seat) ? 'bg-amber-50 dark:bg-amber-950/30' : ''"
            :data-testid="'study-room-list-row-' + seat.code"
          >
            <th
              scope="row"
              tabindex="-1"
              class="px-4 py-2 font-medium whitespace-nowrap focus:outline-2 focus:outline-theme-500"
              :data-seat-code="seat.code"
            >
              {{ seat.label }}
            </th>
            <td class="px-4 py-2">
              <template v-if="seat.isOccupied">
                <span class="mr-1">{{ seat.emoji }}</span>
                <span>{{ seat.nickname || '同學' }}</span>
                <span
                  v-if="grid.isMine(seat)"
                  class="ml-1.5 rounded-full bg-amber-500 px-1.5 text-xs font-semibold text-white"
                  >你</span
                >
              </template>
              <span v-else class="text-theme-700 dark:text-zinc-400">空位</span>
            </td>
            <td class="px-4 py-2">{{ grid.thoughtBubbleText(seat) }}</td>
            <td class="px-4 py-2 font-mono tabular-nums">
              <template v-if="seat.isOccupied && seat.timerMode">
                <span aria-hidden="true">{{ timer.timerLabel(seat) }}</span>
                <span class="sr-only">{{ timer.spokenTimerLabel(seat) }}</span>
              </template>
            </td>
            <td class="px-4 py-2 text-right">
              <button
                v-if="!seat.isOccupied && !socket.heldSeatCode"
                type="button"
                :aria-disabled="grid.isSeatActionable(seat) ? null : 'true'"
                :aria-label="'入座 ' + seat.label"
                class="rounded-lg border border-theme-300 px-3 py-1 text-sm font-medium text-theme-800 transition hover:bg-theme-50 aria-disabled:opacity-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                :data-testid="'study-room-list-take-' + seat.code"
                @click="grid.activateSeat(seat)"
              >
                入座
              </button>
              <button
                v-else-if="grid.isMine(seat)"
                type="button"
                class="rounded-lg border border-amber-400 px-3 py-1 text-sm font-medium text-theme-800 transition hover:bg-amber-100 dark:border-amber-600 dark:text-zinc-200 dark:hover:bg-amber-950/40"
                data-testid="study-room-list-own-seat"
                @click="grid.activateSeat(seat)"
              >
                前往控制列
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
