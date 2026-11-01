<script setup>
// The café as one room, drawn flat the way the study room draws its floors: a
// thick-bordered box with the back wall at the top, then the counter, then
// the wood floor. Unlike the study room it keeps its own dim, warm palette in
// both themes (dark wood, brass lamps, a burgundy rug), like a real café, and
// it has no windows. Flat fills only: no gradients, glows or drop shadows.
//
// The back wall carries shelves of beans and cups, the corkboard and the
// chalk menu board; the corkboard and the menu board are buttons that open
// the wall and the menu in modals, so the room itself is the UI. On the
// counter stand the pastry case, the acorn tip jar, the squirrel and the
// espresso machine. Below it: the stools, the small tables along the left
// wall, the long table on a rug, the arcade cabinet beside the tall plant (a button
// that opens the kiosk in a modal, accesskey `7`) and the door in the right wall.
//
// Seats are the real `CafeSeat` buttons (one roving Tab stop, handled by the
// page), so the drawing never hides who is sitting where.
import { computed } from 'vue'
import AcornIcon from '../Acorns/AcornIcon.vue'
import ArcadeCabinet from './ArcadeCabinet.vue'
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

const emit = defineEmits([
  'activate',
  'open-wall',
  'open-menu',
  'open-arcade',
  'talk',
])

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
// The small tables along the left wall (stored as kind `window`, from before
// the room lost its windows).
const smallTables = computed(() => {
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
    ? `便利貼牆，目前共有 ${props.notes.length} 張便利貼`
    : '便利貼牆，還沒有便利貼'
)

// Pendant lamps over the counter; the middle one only shows from `sm` up,
// where the wall is wide enough for three.
const LAMPS = [
  { position: 'left-[16%]', wide: false },
  { position: 'left-1/2', wide: true },
  { position: 'left-[84%]', wide: false },
]
</script>

<template>
  <div
    class="relative rounded-2xl border-[6px] border-[#5e4535] bg-[#2f231c] shadow-sm [--focus-ring-color:var(--color-amber-300)]"
    data-testid="cafe-floor"
  >
    <!-- ───── back wall ───── -->
    <div
      class="relative overflow-hidden rounded-t-[10px] bg-[#3b2b22] bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(255,255,255,0.04)_5.5rem_calc(5.5rem+1px))] px-3 pt-11"
      data-testid="cafe-scene"
    >
      <!-- pendant lamps and the flat cone of light under each -->
      <div
        v-for="lamp in LAMPS"
        :key="lamp.position"
        class="pointer-events-none absolute top-0 -translate-x-1/2 flex-col items-center"
        :class="[lamp.position, lamp.wide ? 'hidden sm:flex' : 'flex']"
        aria-hidden="true"
      >
        <span class="h-3 w-0.5 bg-[#8a6246]"></span>
        <span class="h-3.5 w-8 rounded-t-full bg-[#c9974f]"></span>
        <span class="-mt-1 size-2.5 rounded-full bg-amber-100"></span>
        <span
          class="-mt-1 h-24 w-36 bg-amber-100/[0.07] [clip-path:polygon(40%_0,60%_0,100%_100%,0_100%)]"
        ></span>
      </div>

      <div class="relative flex items-center justify-center gap-3 sm:gap-5">
        <!-- shelf: beans and a plant -->
        <div
          class="hidden w-24 shrink-0 flex-col gap-4 sm:flex"
          aria-hidden="true"
        >
          <div>
            <div class="flex items-end justify-around px-1">
              <span
                v-for="n in 3"
                :key="n"
                class="relative block w-5 rounded-t-md rounded-b-sm border-2 border-[#d9c4a5] bg-[#5b3a24]"
                :class="n === 2 ? 'h-8' : 'h-7'"
              >
                <span
                  class="absolute -inset-x-0.5 -top-2 h-1.5 rounded-sm bg-[#c9974f]"
                ></span>
              </span>
            </div>
            <div class="h-1.5 rounded-sm bg-[#8a6246]"></div>
          </div>
          <div>
            <div class="flex items-end justify-around px-1">
              <span class="h-8"><CafePlant variant="small" /></span>
              <span class="flex flex-col items-center gap-px">
                <span
                  v-for="n in 3"
                  :key="n"
                  class="block h-2 w-5 rounded-b-md bg-[#f3e6d3]"
                ></span>
              </span>
            </div>
            <div class="h-1.5 rounded-sm bg-[#8a6246]"></div>
          </div>
        </div>

        <!-- the corkboard: click to open the wall -->
        <button
          type="button"
          accesskey="6"
          class="group relative w-36 shrink-0 rounded-md border-4 border-[#8a6246] bg-[#8a6246] p-px text-left transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 sm:w-52"
          :aria-label="wallLabel"
          data-testid="cafe-wall-open"
          @click="emit('open-wall')"
        >
          <StickyNoteBoard :notes="notes" compact />
          <span
            class="pointer-events-none absolute -bottom-2.5 left-1/2 -translate-x-1/2 rounded-full border-2 border-[#8a6246] bg-[#2f231c] px-2 text-[0.625rem] font-semibold whitespace-nowrap text-amber-50 transition group-hover:bg-[#46342a]"
            aria-hidden="true"
            >便利貼牆</span
          >
        </button>

        <!-- chalk menu board: click to order -->
        <button
          type="button"
          class="shrink-0 -rotate-1 rounded-md border-4 border-[#8a6246] bg-[#26302a] px-2.5 py-2 text-center text-xs font-semibold text-amber-50 transition hover:rotate-0 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-default disabled:hover:-rotate-1 sm:px-3 sm:py-2.5"
          :disabled="!canOrder"
          :aria-label="canOrder ? '今日菜單，按一下以點餐' : '今日菜單'"
          data-testid="cafe-menu-board"
          @click="emit('open-menu')"
        >
          <span class="block text-[0.6875rem] tracking-widest whitespace-nowrap"
            >今日菜單</span
          >
          <span
            class="mx-auto mt-1 block h-px w-10 bg-amber-50/30"
            aria-hidden="true"
          ></span>
          <span
            class="mt-1 block leading-snug font-normal whitespace-nowrap text-zinc-300"
            aria-hidden="true"
            >美式<span class="hidden sm:inline">・拿鐵・抹茶拿鐵</span></span
          >
          <span
            class="mt-0.5 block text-[0.625rem] font-normal whitespace-nowrap text-amber-200/80 sm:hidden"
            aria-hidden="true"
            >拿鐵・抹茶</span
          >
        </button>

        <!-- shelf: cups and a bottle -->
        <div
          class="hidden w-24 shrink-0 flex-col gap-4 sm:flex"
          aria-hidden="true"
        >
          <div>
            <div class="flex items-end justify-around px-1">
              <span
                v-for="n in 3"
                :key="n"
                class="relative block h-4 w-5 rounded-b-lg bg-[#f3e6d3] after:absolute after:top-1 after:-right-1.5 after:size-2 after:rounded-full after:border-2 after:border-[#f3e6d3]"
              ></span>
            </div>
            <div class="h-1.5 rounded-sm bg-[#8a6246]"></div>
          </div>
          <div>
            <div class="flex items-end justify-around px-1">
              <span class="flex flex-col items-center">
                <span class="h-2 w-1.5 rounded-t-sm bg-[#4f7a4d]"></span>
                <span
                  class="h-7 w-4 rounded-t-md rounded-b-sm bg-[#4f7a4d]"
                ></span>
              </span>
              <span
                class="relative block h-7 w-6 rounded-t-md rounded-b-sm border-2 border-[#d9c4a5] bg-[#2f1a0e]"
              >
                <span
                  class="absolute -inset-x-0.5 -top-2 h-1.5 rounded-sm bg-[#c9974f]"
                ></span>
              </span>
            </div>
            <div class="h-1.5 rounded-sm bg-[#8a6246]"></div>
          </div>
        </div>
      </div>

      <!-- what stands on the counter, with the squirrel behind it -->
      <div
        class="relative mt-5 flex items-end justify-between gap-2 px-1 sm:px-4"
        aria-hidden="true"
      >
        <div class="flex items-end gap-2 sm:gap-3">
          <!-- pastry case -->
          <div
            class="w-26 rounded-t-lg border-2 border-b-0 border-[#d9c4a5] bg-[#4a372c] px-1 pt-1 sm:w-34"
          >
            <div class="flex justify-around text-sm leading-none sm:text-base">
              <span>🥐</span><span>🍰</span
              ><span class="hidden sm:inline">🥯</span>
            </div>
            <div class="mt-0.5 h-0.5 bg-[#d9c4a5]"></div>
            <div
              class="flex justify-around pt-0.5 text-sm leading-none sm:text-base"
            >
              <span>🍪</span><span>🧁</span
              ><span class="hidden sm:inline">🍩</span>
            </div>
          </div>

          <!-- acorn tip jar -->
          <div class="flex flex-col items-center">
            <span class="-mb-px h-1.5 w-6 rounded-t-sm bg-[#c9974f]"></span>
            <span
              class="flex h-8 w-7 flex-wrap content-end items-end justify-center rounded-b-lg border-2 border-t-0 border-[#d9c4a5] bg-[#4a372c] pb-0.5"
            >
              <AcornIcon size="size-3" />
              <AcornIcon size="size-3" />
              <AcornIcon size="size-3" />
            </span>
          </div>

          <!-- a bud vase -->
          <div class="hidden flex-col items-center sm:flex">
            <span class="size-2.5 rounded-full bg-rose-400"></span>
            <span class="h-3 w-0.5 bg-emerald-500"></span>
            <span
              class="h-5 w-3 rounded-t-full rounded-b-sm bg-sky-300/60"
            ></span>
          </div>
        </div>

        <div class="flex items-end gap-1 sm:gap-2">
          <!-- the squirrel, standing behind the counter -->
          <div class="relative -mb-5">
            <button
              type="button"
              class="block rounded-lg transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2"
              aria-label="和店長聊聊"
              data-testid="cafe-talk-barista"
              @click="emit('talk')"
            >
              <Barista :mood="baristaMood" class="size-18 sm:size-24" />
            </button>
            <slot name="barista-bubble" />
          </div>

          <!-- espresso machine with cups warming on top -->
          <div class="relative">
            <span
              v-for="n in 3"
              :key="n"
              class="absolute top-0 h-3 w-1 animate-steam rounded-full bg-white/70 motion-reduce:animate-none"
              :style="{
                left: `${10 + n * 9}%`,
                animationDelay: `${n * 0.7}s`,
              }"
            ></span>
            <svg
              viewBox="0 0 84 64"
              class="h-12 w-16 sm:h-16 sm:w-21"
              fill="none"
            >
              <rect x="20" y="0" width="10" height="7" rx="2" fill="#f3e6d3" />
              <rect x="34" y="1" width="10" height="6" rx="2" fill="#f3e6d3" />
              <rect x="48" y="0" width="10" height="7" rx="2" fill="#f3e6d3" />
              <rect x="4" y="7" width="76" height="4" rx="2" fill="#c9974f" />
              <rect x="6" y="11" width="72" height="34" rx="4" fill="#b9c0c6" />
              <rect x="12" y="18" width="60" height="7" rx="2" fill="#87919a" />
              <circle cx="66" cy="34" r="5" fill="#3b2b22" />
              <circle cx="66" cy="34" r="3.5" fill="#f3e6d3" />
              <rect x="20" y="45" width="10" height="5" rx="1" fill="#59636c" />
              <rect x="44" y="45" width="10" height="5" rx="1" fill="#59636c" />
              <rect x="20" y="52" width="10" height="9" rx="2" fill="#f3e6d3" />
              <rect x="44" y="52" width="10" height="9" rx="2" fill="#f3e6d3" />
              <rect x="4" y="61" width="76" height="3" rx="1" fill="#59636c" />
            </svg>
          </div>
        </div>
      </div>

      <!-- the counter: wood top, panelled front, brass foot rail -->
      <div class="relative z-10 -mx-3" aria-hidden="true">
        <div class="h-3 bg-[#8a6246]"></div>
        <div
          class="relative h-8 bg-[#4d372b] bg-[repeating-linear-gradient(90deg,transparent_0_2.75rem,#3b2a20_2.75rem_calc(2.75rem+3px))]"
        >
          <span class="absolute inset-x-0 bottom-1.5 h-1 bg-[#c9974f]"></span>
        </div>
      </div>
    </div>

    <!-- ───── the floor ───── -->
    <div
      class="relative rounded-b-[10px] bg-[repeating-linear-gradient(0deg,transparent_0_2.75rem,rgba(255,255,255,0.045)_2.75rem_calc(2.75rem+1px))] px-3 pt-4 pb-18 sm:pr-14 sm:pl-6"
    >
      <p v-if="loading" class="py-10 text-center text-sm text-amber-100/70">
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
          class="mt-6 grid gap-6 sm:grid-cols-[auto_1fr] sm:items-start sm:gap-6"
        >
          <!-- the small tables along the left wall -->
          <div
            class="grid grid-cols-2 gap-x-2 gap-y-3 sm:grid-cols-1 sm:gap-y-2"
            role="group"
            aria-label="靠牆小桌"
            data-testid="cafe-small-tables"
          >
            <div
              v-for="table in smallTables"
              :key="table.code"
              class="flex items-start justify-center gap-0.5"
              role="group"
              :aria-label="`靠牆小桌 ${table.code}`"
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
                class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full border-2 border-[#8a6a54] bg-[#6a4c3a]"
                aria-hidden="true"
              >
                <!-- a tea light -->
                <span
                  class="flex size-3.5 items-center justify-center rounded-full bg-[#f3e6d3]"
                >
                  <span class="size-1.5 rounded-full bg-amber-400"></span>
                </span>
              </span>
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

          <!-- the long table on a rug -->
          <div
            class="relative mx-auto flex w-full max-w-xs flex-col items-center gap-1 rounded-4xl border-4 border-dashed border-[#8c4a42] bg-[#4d2522] px-3 py-4 sm:max-w-none"
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
              class="flex h-12 w-full max-w-56 items-center justify-center gap-2 rounded-xl border-2 border-[#8a6a54] bg-[#6a4c3a]"
              aria-hidden="true"
            >
              <span class="h-7"><CafePlant variant="small" /></span>
              <span class="text-xs font-medium text-amber-50">大長桌</span>
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

      <!-- the arcade cabinet, standing right of the tall plant: click to play -->
      <button
        type="button"
        accesskey="7"
        class="absolute bottom-1 left-14 rounded-lg transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 sm:left-16"
        aria-label="遊樂機台"
        data-testid="cafe-arcade-open"
        @click="emit('open-arcade')"
      >
        <ArcadeCabinet />
      </button>

      <!-- plants in the corners -->
      <div
        class="pointer-events-none absolute bottom-1 left-2 h-16 sm:h-20"
        aria-hidden="true"
      >
        <CafePlant variant="tall" />
      </div>
      <div
        class="pointer-events-none absolute right-20 bottom-1 hidden h-12 sm:block"
        aria-hidden="true"
      >
        <CafePlant variant="bush" />
      </div>

      <!-- the door, in the right wall, like the study room's -->
      <div
        class="pointer-events-none absolute -right-1.5 bottom-3 z-10 h-12 w-1.5 bg-theme-50 dark:bg-zinc-950"
        aria-hidden="true"
      ></div>
      <div
        class="pointer-events-none absolute right-0 bottom-3 z-10 size-12 rounded-tl-full border-t border-l border-dashed border-[#a08068]"
        aria-hidden="true"
      >
        <span
          class="absolute right-0 bottom-0 h-full w-0.75 origin-bottom rotate-[-60deg] rounded-full bg-[#d9c4a5]"
        ></span>
      </div>
      <span
        class="pointer-events-none absolute right-2 bottom-17 text-[0.625rem] text-amber-100/60"
        aria-hidden="true"
        >入口</span
      >
    </div>
  </div>
</template>
