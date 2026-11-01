<script setup>
// The café as one room, drawn the way the study room draws its floors: a
// thick-bordered box with the back wall at the top (window, the corkboard,
// the menu board and the espresso machine), the counter with the squirrel
// behind it and stools in front, then the wood floor with small tables by the
// side window, the long communal table on a rug, plants in the corners and
// the door at the bottom right. The corkboard and the menu board are buttons:
// they open the wall and the menu in modals, so the room itself is the UI.
//
// Seats are the real `CafeSeat` buttons (one roving Tab stop, handled by the
// page), so the drawing never hides who is sitting where.
import { computed } from 'vue'
import Barista from './Barista.vue'
import CafePlant from './CafePlant.vue'
import CafeSeat from './CafeSeat.vue'
import StickyNoteBoard from './StickyNoteBoard.vue'

const props = defineProps({
  seats: { type: Array, required: true },
  socket: { type: Object, required: true },
  canSit: { type: Boolean, required: true },
  canOrder: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  now: { type: Number, required: true },
  tabIndexFor: { type: Function, required: true },
  notes: { type: Array, required: true },
  baristaMood: { type: String, default: 'happy' },
})

const emit = defineEmits(['activate', 'open-wall', 'open-menu'])

const barSeats = computed(() => props.seats.filter(seat => seat.kind === 'bar'))
const communalSeats = computed(() =>
  props.seats.filter(seat => seat.kind === 'communal')
)
const communalTop = computed(() =>
  communalSeats.value.slice(0, Math.ceil(communalSeats.value.length / 2))
)
const communalBottom = computed(() =>
  communalSeats.value.slice(Math.ceil(communalSeats.value.length / 2))
)
const windowTables = computed(() => {
  const tables = new Map()

  for (const seat of props.seats.filter(item => item.kind === 'window')) {
    tables.set(seat.groupCode, [...(tables.get(seat.groupCode) ?? []), seat])
  }

  return [...tables.entries()].map(([code, tableSeats]) => ({
    code,
    seats: tableSeats,
  }))
})

const wallLabel = computed(() =>
  props.notes.length
    ? `便利貼牆，${props.notes.length} 張便利貼，打開看清楚`
    : '便利貼牆，還沒有便利貼，打開看看'
)
</script>

<template>
  <div
    class="relative overflow-hidden rounded-2xl border-[6px] border-theme-300 bg-theme-100/60 shadow-sm dark:border-zinc-600 dark:bg-zinc-900"
    data-testid="cafe-floor"
  >
    <!-- ───── back wall ───── -->
    <div
      class="relative border-b-4 border-theme-300/70 bg-[linear-gradient(to_bottom,var(--color-theme-50),var(--color-theme-100))] px-3 pt-3 dark:border-zinc-700 dark:bg-[linear-gradient(to_bottom,var(--color-zinc-800),var(--color-zinc-900))]"
      data-testid="cafe-scene"
    >
      <div class="flex items-end justify-between gap-3">
        <!-- window with a bit of sky -->
        <div
          class="hidden h-24 w-24 shrink-0 overflow-hidden rounded-t-full border-4 border-theme-300 bg-linear-to-b from-sky-200 to-sky-50 sm:block dark:border-zinc-600 dark:from-indigo-950 dark:to-slate-800"
          aria-hidden="true"
        >
          <div
            class="h-full w-full border-x-2 border-theme-300/70 dark:border-zinc-600/70"
          >
            <span
              class="mt-6 block h-3 w-8 rounded-full bg-white/70 dark:bg-zinc-400/20"
            ></span>
          </div>
        </div>

        <!-- the corkboard: click to open the wall -->
        <button
          type="button"
          accesskey="6"
          class="group relative w-32 shrink-0 rounded-md border-4 border-amber-900/60 bg-amber-900/60 p-px text-left shadow-md transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 sm:w-52 dark:border-amber-800/70 dark:bg-amber-800/70"
          :aria-label="wallLabel"
          data-testid="cafe-wall-open"
          @click="emit('open-wall')"
        >
          <StickyNoteBoard :notes="notes" compact />
          <span
            class="pointer-events-none absolute -bottom-2 left-1/2 -translate-x-1/2 rounded-full bg-theme-800 px-2 py-px text-[0.625rem] font-semibold whitespace-nowrap text-white shadow-sm transition group-hover:bg-theme-900 dark:bg-zinc-700"
            aria-hidden="true"
            >便利貼牆</span
          >
        </button>

        <!-- menu board: click to order -->
        <button
          type="button"
          class="shrink-0 -rotate-1 rounded-md border-4 border-amber-900/70 bg-zinc-800 px-2 py-2 text-center text-xs font-semibold text-amber-100 shadow-sm transition hover:rotate-0 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-default disabled:hover:-rotate-1 sm:px-3"
          :disabled="!canOrder"
          :aria-label="
            canOrder ? '今日菜單，打開點餐' : '今日菜單（入座後才能點餐）'
          "
          data-testid="cafe-menu-board"
          @click="emit('open-menu')"
        >
          <span class="block text-[0.6875rem] tracking-widest whitespace-nowrap"
            >今日菜單</span
          >
          <span
            class="mt-0.5 hidden font-normal whitespace-nowrap text-zinc-300 sm:block"
            >美式・拿鐵・抹茶拿鐵</span
          >
        </button>

        <!-- the squirrel and the espresso machine behind the counter -->
        <div class="relative flex shrink-0 items-end gap-1" aria-hidden="true">
          <div class="relative -mb-4">
            <Barista :mood="baristaMood" class="size-16 sm:size-24" />
          </div>
          <div class="relative hidden sm:block">
            <span
              v-for="n in 3"
              :key="n"
              class="absolute -top-3 h-3 w-1 animate-steam rounded-full bg-white/80 motion-reduce:animate-none dark:bg-zinc-300/60"
              :style="{
                left: `${8 + n * 10}px`,
                animationDelay: `${n * 0.7}s`,
              }"
            ></span>
            <svg viewBox="0 0 80 56" class="h-14 w-20" fill="none">
              <rect
                x="4"
                y="6"
                width="72"
                height="34"
                rx="5"
                class="fill-zinc-500 dark:fill-zinc-600"
              />
              <rect
                x="10"
                y="12"
                width="60"
                height="8"
                rx="2"
                class="fill-zinc-300 dark:fill-zinc-400"
              />
              <rect
                x="22"
                y="40"
                width="12"
                height="10"
                rx="1.5"
                class="fill-white dark:fill-zinc-200"
              />
              <rect
                x="46"
                y="40"
                width="12"
                height="10"
                rx="1.5"
                class="fill-white dark:fill-zinc-200"
              />
              <circle cx="62" cy="28" r="3" class="fill-amber-400" />
            </svg>
          </div>
        </div>
      </div>

      <!-- the counter: wood top, panelled front -->
      <div class="relative z-10 -mx-3 mt-2" aria-hidden="true">
        <div
          class="h-3 bg-amber-800 shadow-[inset_0_2px_0_rgba(255,255,255,0.25)] dark:bg-amber-950"
        ></div>
        <div
          class="h-6 bg-[repeating-linear-gradient(90deg,var(--color-amber-700)_0_2.5rem,var(--color-amber-800)_2.5rem_calc(2.5rem+3px))] dark:bg-[repeating-linear-gradient(90deg,var(--color-amber-900)_0_2.5rem,var(--color-amber-950)_2.5rem_calc(2.5rem+3px))]"
        ></div>
      </div>
    </div>

    <!-- ───── the floor ───── -->
    <div
      class="relative bg-[repeating-linear-gradient(0deg,transparent_0_2.75rem,rgba(120,72,20,0.08)_2.75rem_calc(2.75rem+1px))] px-3 pt-3 pb-14 sm:px-5 dark:bg-[repeating-linear-gradient(0deg,transparent_0_2.75rem,rgba(255,255,255,0.05)_2.75rem_calc(2.75rem+1px))]"
    >
      <p
        v-if="loading"
        class="py-10 text-center text-sm text-theme-700 dark:text-zinc-400"
      >
        正在開門…
      </p>

      <template v-else>
        <!-- stools at the counter -->
        <div
          class="flex flex-wrap justify-center gap-x-2 gap-y-1 sm:justify-around sm:px-10"
          role="group"
          aria-label="吧台"
          data-testid="cafe-bar"
        >
          <CafeSeat
            v-for="seat in barSeats"
            :key="seat.code"
            :seat="seat"
            :socket="socket"
            :can-sit="canSit"
            :roving-tabindex="tabIndexFor(seat)"
            :now="now"
            variant="stool"
            @activate="emit('activate', $event)"
          />
        </div>

        <div
          class="mt-6 grid gap-6 sm:grid-cols-[auto_1fr] sm:items-start sm:gap-4"
        >
          <!-- window side: a tall window strip and the small tables -->
          <div class="relative flex gap-2">
            <div
              class="hidden w-3 shrink-0 self-stretch rounded-full border-2 border-theme-300 bg-linear-to-b from-sky-200/80 to-sky-50 sm:block dark:border-zinc-600 dark:from-indigo-950 dark:to-slate-800"
              aria-hidden="true"
            ></div>
            <div
              class="grid flex-1 grid-cols-2 gap-x-2 gap-y-3 sm:grid-cols-1 sm:gap-y-2"
              role="group"
              aria-label="窗邊小桌"
              data-testid="cafe-window-tables"
            >
              <div
                v-for="table in windowTables"
                :key="table.code"
                class="flex items-center justify-center gap-0.5"
                role="group"
                :aria-label="`窗邊小桌 ${table.code}`"
              >
                <CafeSeat
                  v-if="table.seats[0]"
                  :seat="table.seats[0]"
                  :socket="socket"
                  :can-sit="canSit"
                  :roving-tabindex="tabIndexFor(table.seats[0])"
                  :now="now"
                  backrest="left"
                  @activate="emit('activate', $event)"
                />
                <span
                  class="flex size-9 shrink-0 items-center justify-center rounded-full border-2 border-theme-300 bg-theme-200 text-xs shadow-[inset_0_2px_0_rgba(255,255,255,0.6),0_2px_4px_rgba(0,0,0,0.06)] dark:border-zinc-600 dark:bg-zinc-700 dark:shadow-none"
                  aria-hidden="true"
                  >☕</span
                >
                <CafeSeat
                  v-if="table.seats[1]"
                  :seat="table.seats[1]"
                  :socket="socket"
                  :can-sit="canSit"
                  :roving-tabindex="tabIndexFor(table.seats[1])"
                  :now="now"
                  backrest="right"
                  @activate="emit('activate', $event)"
                />
              </div>
            </div>
          </div>

          <!-- the long table on a rug -->
          <div
            class="relative mx-auto flex w-full max-w-xs flex-col items-center gap-1 rounded-[2rem] border-4 border-dashed border-rose-300/60 bg-rose-100/50 px-3 py-4 sm:max-w-none dark:border-rose-900/50 dark:bg-rose-950/20"
            role="group"
            aria-label="大長桌"
            data-testid="cafe-communal"
          >
            <div class="flex justify-center gap-2 sm:gap-4">
              <CafeSeat
                v-for="seat in communalTop"
                :key="seat.code"
                :seat="seat"
                :socket="socket"
                :can-sit="canSit"
                :roving-tabindex="tabIndexFor(seat)"
                :now="now"
                backrest="top"
                @activate="emit('activate', $event)"
              />
            </div>
            <div
              class="flex h-12 w-full max-w-56 items-center justify-center gap-2 rounded-xl border-2 border-theme-300 bg-theme-200 shadow-[inset_0_2px_0_rgba(255,255,255,0.6),0_2px_4px_rgba(0,0,0,0.06)] dark:border-zinc-600 dark:bg-zinc-700 dark:shadow-none"
              aria-hidden="true"
            >
              <span class="h-7"><CafePlant variant="small" /></span>
              <span
                class="text-xs font-medium text-theme-800 dark:text-zinc-300"
                >大長桌</span
              >
              <span class="text-base leading-none">📖</span>
            </div>
            <div class="flex justify-center gap-2 sm:gap-4">
              <CafeSeat
                v-for="seat in communalBottom"
                :key="seat.code"
                :seat="seat"
                :socket="socket"
                :can-sit="canSit"
                :roving-tabindex="tabIndexFor(seat)"
                :now="now"
                backrest="bottom"
                @activate="emit('activate', $event)"
              />
            </div>
          </div>
        </div>
      </template>

      <!-- plants in the corners -->
      <div
        class="pointer-events-none absolute bottom-1 left-2 h-16 sm:h-20"
        aria-hidden="true"
      >
        <CafePlant variant="tall" />
      </div>
      <div
        class="pointer-events-none absolute right-24 bottom-1 hidden h-12 sm:block"
        aria-hidden="true"
      >
        <CafePlant variant="bush" />
      </div>

      <!-- the door, like the study room's -->
      <div
        class="pointer-events-none absolute right-8 -bottom-[6px] z-10 h-[6px] w-12 bg-theme-50 dark:bg-zinc-950"
        aria-hidden="true"
      ></div>
      <div
        class="pointer-events-none absolute right-8 bottom-0 z-10 size-12 rounded-tl-full border-t border-l border-dashed border-theme-400 dark:border-zinc-500"
        aria-hidden="true"
      >
        <span
          class="absolute right-0 bottom-0 h-full w-[3px] origin-bottom -rotate-[70deg] rounded-full bg-theme-500 dark:bg-zinc-400"
        ></span>
      </div>
      <span
        class="pointer-events-none absolute right-2 bottom-12 text-[0.625rem] text-theme-600 dark:text-zinc-500"
        aria-hidden="true"
        >入口</span
      >
    </div>
  </div>
</template>
