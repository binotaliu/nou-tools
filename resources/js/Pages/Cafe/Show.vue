<script setup>
// 咖啡廳 — the shell. Live seat state comes from useCafeSocket (REST + Echo),
// not from Inertia props, the same split the study room uses. The room is
// drawn by CafeFloor; the wall, the menu and the note composer open in
// modals from it (and from the toolbar), so nothing stacks under the map.
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Modal from '../../Components/StudyRoom/Modal.vue'
import BaristaDialogue from '../../Components/Cafe/BaristaDialogue.vue'
import CafeFloor from '../../Components/Cafe/CafeFloor.vue'
import StickyNoteComposer from '../../Components/Cafe/StickyNoteComposer.vue'
import StickyNoteWall from '../../Components/Cafe/StickyNoteWall.vue'
import MenuSheet from '../../Components/Cafe/MenuSheet.vue'
import AcornIcon from '../../Components/Acorns/AcornIcon.vue'
import useAcorns from '../../Composables/useAcorns'
import useBarista from '../../Composables/useBarista'
import useCafeSocket from '../../Composables/useCafeSocket'
import useSessionClock from '../../Composables/useSessionClock'

const props = defineProps({
  cafe: { type: Object, required: true },
})

const socket = useCafeSocket({
  heartbeatIntervalSeconds: props.cafe.heartbeatIntervalSeconds,
})

const acorns = useAcorns()
const { now } = useSessionClock()

const menu = ref([])
const menuOpen = ref(false)
const orderBusyId = ref(null)
const orderError = ref(null)

const notes = ref([])
const wallOpen = ref(false)
const composerOpen = ref(false)
const noteMessage = ref(null)

const barista = useBarista({
  onAction(action) {
    if (action === 'order') {
      openMenu()
    } else if (action === 'note') {
      openComposer()
    }
  },
})

const canOrder = computed(
  () => props.cafe.hasSchedule && props.cafe.hasNickname
)

async function openMenu() {
  if (!canOrder.value) {
    return
  }

  menuOpen.value = true
  orderError.value = null

  if (!menu.value.length) {
    try {
      menu.value = (await window.axios.get('/cafe/menu')).data.menu
    } catch {
      orderError.value = '菜單暫時打不開，請稍後再試。'
    }
  }
}

async function loadNotes() {
  try {
    notes.value = (await window.axios.get('/cafe/notes')).data.notes
  } catch {
    // The wall keeps what it had.
  }
}

function openComposer() {
  if (!canOrder.value) {
    return
  }

  wallOpen.value = false
  composerOpen.value = true
  noteMessage.value = null
}

async function notePosted(data) {
  Object.assign(acorns, data.acorns, { loaded: true })
  composerOpen.value = false
  noteMessage.value = '便利貼收到了，檢查通過就會貼上牆。'
  await loadNotes()
}

function reportNote(note) {
  window.axios.post(`/cafe/notes/${note.id}/report`).catch(() => {})
}

async function order({ item, toGo }) {
  orderBusyId.value = item.id
  orderError.value = null

  try {
    const response = await window.axios.post('/cafe/orders', {
      menuItemId: item.id,
      toGo,
    })

    Object.assign(acorns, response.data.acorns, { loaded: true })
    menuOpen.value = false
    socket.refresh()
    await barista.talk(item.name)
  } catch (error) {
    orderError.value =
      error.response?.data?.message ?? '網路好像不太穩定，請稍後再試一次。'
  } finally {
    orderBusyId.value = null
  }
}

const canSit = computed(() => props.cafe.hasSchedule && props.cafe.hasNickname)

const seats = computed(() => socket.seats())

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
  socket.onNoteEvent(() => loadNotes())
  import('../../echo')
  await Promise.all([
    socket.load(),
    loadNotes(),
    props.cafe.hasSchedule ? acorns.load() : null,
  ])
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
        <span
          v-if="acorns.loaded"
          class="inline-flex items-center gap-1 rounded-full bg-theme-100 px-2.5 py-1 text-xs font-semibold text-theme-800 tabular-nums dark:bg-zinc-800 dark:text-amber-300"
          data-testid="cafe-acorns"
        >
          <AcornIcon /> 你有 {{ acorns.balance }} 顆橡實
        </span>
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

      <p
        v-if="noteMessage"
        role="status"
        class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"
        data-testid="cafe-note-message"
      >
        {{ noteMessage }}
      </p>

      <!-- toolbar, like the study room's RoomToolbar -->
      <div
        class="flex flex-wrap items-center gap-2"
        role="toolbar"
        aria-label="咖啡廳操作"
      >
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
        <template v-if="canOrder">
          <button
            type="button"
            class="rounded-lg border-2 border-b-4 border-theme-300 bg-white px-3 py-1.5 text-sm font-semibold text-theme-900 transition active:translate-y-px active:border-b-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            data-testid="cafe-open-menu"
            @click="openMenu"
          >
            點餐
          </button>
          <button
            type="button"
            class="rounded-lg border-2 border-b-4 border-theme-300 bg-white px-3 py-1.5 text-sm font-semibold text-theme-900 transition active:translate-y-px active:border-b-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            data-testid="cafe-open-note"
            @click="openComposer"
          >
            買便利貼
          </button>
        </template>
      </div>

      <div @keydown="handleKeydown">
        <p id="cafe-seat-keys-hint" class="sr-only">
          用方向鍵在座位間移動，Home、End 移到第一個或最後一個座位。
        </p>
        <CafeFloor
          :seats="seats"
          :socket="socket"
          :can-sit="canSit"
          :can-order="canOrder"
          :loading="socket.loading.value && !socket.state.value"
          :now="now"
          :tab-index-for="tabIndexFor"
          :notes="notes"
          :barista-mood="barista.mood"
          aria-describedby="cafe-seat-keys-hint"
          @activate="activate"
          @open-wall="wallOpen = true"
          @open-menu="openMenu"
          @talk="barista.talk()"
        >
          <template #barista-bubble>
            <BaristaDialogue
              v-if="barista.open"
              :barista="barista"
              @more="barista.talk()"
              @close="barista.close()"
            />
          </template>
        </CafeFloor>
      </div>

      <ul
        class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-xs text-theme-700 dark:text-zinc-400"
      >
        <li class="inline-flex items-center gap-1.5">
          <span
            class="size-3 rounded-sm border-2 border-b-[3px] border-[#7d604c] bg-[#46342a]"
          ></span>
          空位
        </li>
        <li class="inline-flex items-center gap-1.5">
          <span
            class="size-3 rounded-sm border-2 border-[#d9c4a5] bg-[#f3e6d3]"
          ></span>
          有人
        </li>
        <li class="inline-flex items-center gap-1.5">
          <span
            class="rounded-full bg-amber-700 px-1 text-xs leading-4 font-semibold text-white"
            >你</span
          >
          你的座位
        </li>
        <li class="inline-flex items-center gap-1.5">
          <span aria-hidden="true">☕</span>
          桌上有飲料
        </li>
      </ul>

      <Modal
        :open="wallOpen"
        title="便利貼牆"
        max-width="max-w-2xl"
        data-testid="cafe-wall-modal"
        @close="wallOpen = false"
      >
        <StickyNoteWall v-if="wallOpen" :notes="notes" @report="reportNote" />
        <template #footer>
          <button
            v-if="canOrder"
            type="button"
            class="mr-auto rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1.5 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 dark:border-theme-900 dark:bg-theme-700"
            data-testid="cafe-wall-compose"
            @click="openComposer"
          >
            我也要貼一張
          </button>
          <button
            type="button"
            class="rounded-lg border-2 border-b-4 border-theme-300 bg-white px-3 py-1.5 text-sm font-medium text-theme-900 transition active:translate-y-px active:border-b-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            @click="wallOpen = false"
          >
            關閉
          </button>
        </template>
      </Modal>

      <Modal
        :open="menuOpen"
        max-width="max-w-md"
        data-testid="cafe-menu-modal"
        @close="menuOpen = false"
      >
        <MenuSheet
          v-if="menuOpen"
          :menu="menu"
          :balance="acorns.balance"
          :busy-item-id="orderBusyId"
          :error-message="orderError"
          @order="order"
          @close="menuOpen = false"
        />
      </Modal>

      <Modal
        :open="composerOpen"
        title="寫一張便利貼"
        max-width="max-w-lg"
        data-testid="cafe-note-modal"
        @close="composerOpen = false"
      >
        <StickyNoteComposer
          v-if="composerOpen"
          :price="cafe.notePrice"
          :max-length="cafe.noteMaxLength"
          :lifetime-days="cafe.noteLifetimeDays"
          :balance="acorns.balance"
          :notes="notes"
          @posted="notePosted"
          @close="composerOpen = false"
        />
      </Modal>
    </div>
  </AppLayout>
</template>
