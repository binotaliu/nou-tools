<script setup>
// 咖啡廳 — the shell. Live seat state comes from useCafeSocket (REST + Echo),
// not from Inertia props, the same split the study room uses.
import { computed, nextTick, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import CafeScene from '../../Components/Cafe/CafeScene.vue'
import CafeSeat from '../../Components/Cafe/CafeSeat.vue'
import useCafeSocket from '../../Composables/useCafeSocket'

const props = defineProps({
  cafe: { type: Object, required: true },
})

const socket = useCafeSocket({
  heartbeatIntervalSeconds: props.cafe.heartbeatIntervalSeconds,
})

const canSit = computed(() => props.cafe.hasSchedule && props.cafe.hasNickname)

const seats = computed(() => socket.seats())

const barSeats = computed(() => seats.value.filter(seat => seat.kind === 'bar'))
const communalSeats = computed(() =>
  seats.value.filter(seat => seat.kind === 'communal')
)
const windowTables = computed(() => {
  const tables = new Map()

  for (const seat of seats.value.filter(item => item.kind === 'window')) {
    tables.set(seat.groupCode, [...(tables.get(seat.groupCode) ?? []), seat])
  }

  return [...tables.entries()].map(([code, tableSeats]) => ({
    code,
    seats: tableSeats,
  }))
})

// One Tab stop for the whole room; arrows move between seats in DOM order.
const activeCode = computed(() => {
  const list = seats.value

  return (
    socket.heldSeatCode.value ??
    list.find(seat => !seat.isOccupied)?.code ??
    list[0]?.code ??
    null
  )
})

function tabIndexFor(seat) {
  return seat.code === activeCode.value ? 0 : -1
}

function handleKeydown(event) {
  const keys = {
    ArrowRight: 1,
    ArrowDown: 1,
    ArrowLeft: -1,
    ArrowUp: -1,
  }
  const buttons = [...document.querySelectorAll('[data-seat-code]')]
  const index = buttons.indexOf(document.activeElement)

  if (index === -1) {
    return
  }

  let next = null

  if (event.key in keys) {
    next = buttons[(index + keys[event.key] + buttons.length) % buttons.length]
  } else if (event.key === 'Home') {
    next = buttons[0]
  } else if (event.key === 'End') {
    next = buttons[buttons.length - 1]
  }

  if (next) {
    event.preventDefault()
    next.focus()
  }
}

function activate(seat) {
  if (seat.isYou || seat.isOccupied) {
    return
  }

  if (!canSit.value) {
    return
  }

  socket.take(seat.code)
}

function sitDownQuickly() {
  const free = socket.firstFreeSeat()

  if (free) {
    activate(free)
  }
}

socket.onSeatEvent(async ({ type, code }) => {
  if (type === 'left' || type === 'released') {
    await nextTick()
    document.querySelector('[data-testid="cafe-quick-seat"]')?.focus()
  } else if (type === 'taken') {
    await nextTick()
    document.querySelector(`[data-seat-code="${code}"]`)?.focus()
  }
})

onMounted(async () => {
  socket.start()
  import('../../echo')
  await socket.load()
})

onUnmounted(() => {
  socket.stop()

  // Leaving the page gives the seat back; the idle sweep is the fallback.
  if (socket.heldSeatCode.value) {
    socket.leave()
  }
})
</script>

<template>
  <AppLayout>
    <Head title="咖啡廳" />

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-6" data-testid="cafe-root">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
          <h1 class="text-2xl font-bold text-theme-900 dark:text-zinc-100">
            咖啡廳
          </h1>
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            坐下來喝杯咖啡，跟隔壁的同學打個招呼。
            <span v-if="socket.state.value" data-testid="cafe-occupants">
              現在有 {{ socket.state.value.occupantCount }} 人在店裡。
            </span>
          </p>
        </div>
        <div class="flex gap-2">
          <button
            v-if="!socket.heldSeatCode.value"
            type="button"
            accesskey="5"
            data-testid="cafe-quick-seat"
            class="rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1.5 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 disabled:opacity-50 dark:border-theme-900 dark:bg-theme-700"
            :disabled="!canSit || !socket.firstFreeSeat()"
            @click="sitDownQuickly"
          >
            快速入座
          </button>
          <button
            v-else
            type="button"
            data-testid="cafe-leave"
            class="rounded-lg border-2 border-b-4 border-theme-300 bg-white px-3 py-1.5 text-sm font-semibold text-theme-900 transition active:translate-y-px active:border-b-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            @click="socket.leave()"
          >
            離開座位
          </button>
        </div>
      </div>

      <div
        v-if="!cafe.hasSchedule"
        class="rounded-lg border-2 border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100"
        data-testid="cafe-needs-schedule"
      >
        建立課表（或找回你的課表）就能入座。
        <Link href="/schedules/create" class="font-semibold underline"
          >建立課表</Link
        >
        ・
        <Link href="/schedules/my" class="font-semibold underline"
          >找回課表</Link
        >
      </div>
      <div
        v-else-if="!cafe.hasNickname"
        class="rounded-lg border-2 border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100"
        data-testid="cafe-needs-nickname"
      >
        先到
        <Link href="/study-room" class="font-semibold underline">自習室</Link>
        設定暱稱，才能在咖啡廳入座。
      </div>

      <p
        v-if="socket.errorMessage.value"
        role="alert"
        class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-900 dark:bg-red-950/50 dark:text-red-200"
        data-testid="cafe-error"
      >
        {{ socket.errorMessage.value }}
      </p>

      <div @keydown="handleKeydown">
        <CafeScene />

        <div
          class="space-y-5 rounded-b-xl border-[6px] border-t-0 border-theme-300 bg-theme-50 p-4 dark:border-zinc-600 dark:bg-zinc-900"
          data-testid="cafe-floor"
        >
          <p
            v-if="socket.loading.value && !socket.state.value"
            class="py-6 text-center text-sm text-theme-700 dark:text-zinc-400"
          >
            正在開門…
          </p>

          <template v-else>
            <section aria-labelledby="cafe-bar-heading">
              <h2
                id="cafe-bar-heading"
                class="mb-1.5 text-xs font-semibold tracking-widest text-theme-700 dark:text-zinc-400"
              >
                吧台
              </h2>
              <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                <CafeSeat
                  v-for="seat in barSeats"
                  :key="seat.code"
                  :seat="seat"
                  :socket="socket"
                  :can-sit="canSit"
                  :roving-tabindex="tabIndexFor(seat)"
                  variant="stool"
                  @activate="activate"
                />
              </div>
            </section>

            <section aria-labelledby="cafe-window-heading">
              <h2
                id="cafe-window-heading"
                class="mb-1.5 text-xs font-semibold tracking-widest text-theme-700 dark:text-zinc-400"
              >
                窗邊小桌
              </h2>
              <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                  v-for="table in windowTables"
                  :key="table.code"
                  class="rounded-xl border-2 border-dashed border-theme-300 p-1.5 dark:border-zinc-600"
                >
                  <div class="grid grid-cols-2 gap-1.5">
                    <CafeSeat
                      v-for="seat in table.seats"
                      :key="seat.code"
                      :seat="seat"
                      :socket="socket"
                      :can-sit="canSit"
                      :roving-tabindex="tabIndexFor(seat)"
                      @activate="activate"
                    />
                  </div>
                </div>
              </div>
            </section>

            <section aria-labelledby="cafe-communal-heading">
              <h2
                id="cafe-communal-heading"
                class="mb-1.5 text-xs font-semibold tracking-widest text-theme-700 dark:text-zinc-400"
              >
                大長桌
              </h2>
              <div
                class="rounded-xl border-2 border-dashed border-theme-300 p-1.5 dark:border-zinc-600"
              >
                <div class="grid grid-cols-3 gap-1.5 sm:grid-cols-6">
                  <CafeSeat
                    v-for="seat in communalSeats"
                    :key="seat.code"
                    :seat="seat"
                    :socket="socket"
                    :can-sit="canSit"
                    :roving-tabindex="tabIndexFor(seat)"
                    @activate="activate"
                  />
                </div>
              </div>
            </section>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
