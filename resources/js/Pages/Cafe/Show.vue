<script setup>
// 咖啡廳 — the shell. Live seat state comes from useCafeSocket (REST + Echo),
// not from Inertia props, the same split the study room uses. The room is
// drawn by CafeFloor; the wall, the menu and the note composer open in
// modals from it (and from the toolbar), so nothing stacks under the map.
//
// A café seat is a study-room seat in another room: once seated, the study
// room's control panel (ActionBanner, FocusMode, useStudyTimer) runs here
// too, with a coffee tray on top. 內用 needs the seat and is thrown away when
// it is left; 外帶 can be ordered without one.
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Modal from '../../Components/StudyRoom/Modal.vue'
import ActionBanner from '../../Components/StudyRoom/ActionBanner.vue'
import FocusMode from '../../Components/StudyRoom/FocusMode.vue'
import CoffeeTray from '../../Components/Cafe/CoffeeTray.vue'
import BaristaDialogue from '../../Components/Cafe/BaristaDialogue.vue'
import CassettePlayer from '../../Components/StudyRoom/CassettePlayer.vue'
import CafeFloor from '../../Components/Cafe/CafeFloor.vue'
import StickyNoteComposer from '../../Components/Cafe/StickyNoteComposer.vue'
import StickyNoteWall from '../../Components/Cafe/StickyNoteWall.vue'
import MenuSheet from '../../Components/Cafe/MenuSheet.vue'
import AcornIcon from '../../Components/Acorns/AcornIcon.vue'
import useAcorns from '../../Composables/useAcorns'
import useBarista from '../../Composables/useBarista'
import useCafeSocket from '../../Composables/useCafeSocket'
import useSessionClock from '../../Composables/useSessionClock'
import useStudyRoomBackground from '../../Composables/useStudyRoomBackground'
import useStudyRoomMusic from '../../Composables/useStudyRoomMusic'
import useStudyRoomSky from '../../Composables/useStudyRoomSky'
import useStudyTimer from '../../Composables/useStudyTimer'
import useTwemoji from '../../Composables/useTwemoji'

const props = defineProps({
  cafe: { type: Object, required: true },
})

const socket = useCafeSocket({
  heartbeatIntervalSeconds: props.cafe.heartbeatIntervalSeconds,
})

const acorns = useAcorns()
// Emoji look the same everywhere, as in the study room.
const twemoji = useTwemoji()
// A second-by-second clock: a drink appears a few seconds after it is ordered.
const { now } = useSessionClock(1000)
const background = useStudyRoomBackground()
const music = useStudyRoomMusic()
const sky = useStudyRoomSky(props.cafe.timerConfig)

// The two profile fields the timer and focus mode read.
const profile = reactive({
  nickname: props.cafe.nickname,
  emoji: props.cafe.emoji,
  playSoundOnTimerEnd: props.cafe.playSoundOnTimerEnd,
})

const timer = useStudyTimer(
  socket,
  props.cafe.timerConfig,
  props.cafe.pomodoroCycle,
  profile,
  props.cafe.verbs,
  props.cafe.subjects,
  { title: '咖啡廳 - NOU 小幫手' }
)

socket.onStateChange(() => {
  timer.onRoomStateChanged(() => sky.unmountSkyCanvas('focus'))
})

const menu = ref([])
const menuOpen = ref(false)
const orderBusyId = ref(null)
const orderError = ref(null)
const trayBusy = ref(false)
const trayError = ref(null)

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
      orderError.value = '無法開啟菜單，請稍後再試。'
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
  noteMessage.value = '已收到你的便利貼，稍等一下松鼠店長就會把它貼到牆上。'
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
    await socket.refresh()
    await barista.talk(item.name)
  } catch (error) {
    orderError.value =
      error.response?.data?.message ?? '網路好像不太穩定，請稍後再試一次。'
  } finally {
    orderBusyId.value = null
  }
}

// Reordering from the tray: the same drink, the same way, no menu.
async function reorder(coffee) {
  trayBusy.value = true
  trayError.value = null

  try {
    const response = await window.axios.post('/cafe/orders', {
      menuItemId: coffee.menuItemId,
      // You are sitting here (the tray only shows on a seat), so it is dine-in
      // even if the cup you finished was a takeaway you brought in.
      toGo: false,
    })

    Object.assign(acorns, response.data.acorns, { loaded: true })
    await socket.refresh()
  } catch (error) {
    trayError.value = socket.resolveErrorMessage(error)
  } finally {
    trayBusy.value = false
  }
}

async function sip() {
  trayBusy.value = true
  trayError.value = null

  try {
    await window.axios.post('/cafe/orders/current/sip')
    await socket.refresh()
  } catch (error) {
    trayError.value = socket.resolveErrorMessage(error)
  } finally {
    trayBusy.value = false
  }
}

async function putAway() {
  trayBusy.value = true
  trayError.value = null

  try {
    await window.axios.delete('/cafe/orders/current')
    await socket.refresh()
  } catch (error) {
    trayError.value = socket.resolveErrorMessage(error)
  } finally {
    trayBusy.value = false
  }
}

const canSit = computed(() => props.cafe.hasSchedule && props.cafe.hasNickname)

const seats = computed(() => socket.seats())

// The cup on your own table, once it has been made (for focus mode).
const coffeeOnTable = computed(() => {
  const coffee = socket.mySeat()?.coffee

  return coffee &&
    new Date(coffee.readyAt).getTime() <= now.value &&
    new Date(coffee.expiresAt).getTime() > now.value
    ? coffee
    : null
})

// One Tab stop for the whole room; arrows move between seats in DOM order.
const activeCode = computed(() => {
  const list = seats.value

  return (
    socket.heldSeatCode ??
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

// Minutes someone may sit without ordering before the seat is given back.
const graceMinutes = Math.max(1, Math.round(props.cafe.orderGraceSeconds / 60))

// The first control of the panel that is showing, like the study room's.
const OWN_SEAT_FOCUS = [
  '[data-testid="study-room-verb-group"] input:checked',
  '[data-testid="study-room-resume-timer"]',
  '[data-testid="study-room-next-round"]',
  '[data-testid="study-room-pause-timer"]',
  '[data-testid="study-room-start-break"]',
  '[data-testid="study-room-control-panel-heading"]',
]

async function openControlPanel() {
  const expand = document.querySelector(
    '[data-testid="study-room-banner-expand"]'
  )

  if (expand && expand.getClientRects().length > 0) {
    expand.click()
    await nextTick()
  }

  for (const selector of OWN_SEAT_FOCUS) {
    const target = document.querySelector(selector)

    if (target && target.closest('[style*="display: none"]') === null) {
      target.focus()

      return
    }
  }
}

function activate(seat) {
  // Your own seat leads to the control panel.
  if (seat.isYou) {
    openControlPanel()

    return
  }

  if (seat.isOccupied) {
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

socket.onSeatEvent(async ({ type, code, reason }) => {
  if (type === 'released' && reason === 'seat.unordered-released') {
    socket.errorMessage = `由於你沒有點餐，松鼠店長已請你將座位讓給其他同學。`
  }

  if (type === 'left' || type === 'released') {
    await nextTick()
    document.querySelector('[data-testid="cafe-quick-seat"]')?.focus()
  } else if (type === 'taken') {
    await nextTick()
    await openControlPanel()
  }
})

function handleVisibilityChange() {
  timer.updateTabIndicators()

  if (!document.hidden) {
    timer.suppressAlreadyFinishedSound()
    socket.refresh()
    socket.heartbeat()
  }
}

onMounted(async () => {
  timer.initTabIndicators()
  sky.startClock()
  // The page's own socket and heartbeat take over from the shell's.
  background.stopFor('cafe')
  music.load()
  document.addEventListener('visibilitychange', handleVisibilityChange)
  socket.start()
  socket.onNoteEvent(() => loadNotes())
  import('../../echo')
  await Promise.all([
    socket.load(),
    loadNotes(),
    props.cafe.hasSchedule ? acorns.load() : null,
  ])

  // The whole body, not just the page: the menu, wall and note dialogs are
  // teleported to <body>, outside `cafe-root`.
  twemoji.start(document.body)
})

onUnmounted(() => {
  sky.stopClock()
  socket.stop()
  twemoji.stop()
  document.removeEventListener('visibilitychange', handleVisibilityChange)

  // Leaving the page keeps the seat: the shell carries its timer readout and
  // heartbeat (the idle sweep is the fallback if the tab is simply closed).
  if (socket.heldSeatCode && socket.mySeat()) {
    background.handOff(socket.mySeat(), {
      playSound: profile.playSoundOnTimerEnd,
      heartbeatIntervalSeconds: props.cafe.heartbeatIntervalSeconds,
      room: 'cafe',
    })
  } else {
    background.clearFor('cafe')
  }
})
</script>

<template>
  <AppLayout>
    <Head title="咖啡廳 - NOU 小幫手" />

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-6" data-testid="cafe-root">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
          <h1 class="text-2xl font-bold text-theme-900 dark:text-zinc-100">
            咖啡廳
          </h1>
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            歡迎光臨咖啡廳。
            <span v-if="socket.state" data-testid="cafe-occupants">
              目前有 {{ socket.state.occupantCount }} 個同學在店裡。
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
        建立課表後即可入座。
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
        設定暱稱後方可入座。
      </div>

      <p
        v-if="socket.errorMessage"
        role="alert"
        class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-900 dark:bg-red-950/50 dark:text-red-200"
        data-testid="cafe-error"
      >
        {{ socket.errorMessage }}
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
          v-if="!socket.heldSeatCode"
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
          用方向鍵在座位間移動，Home、End 可移到第一或最後一個座位。
        </p>
        <CafeFloor
          :seats="seats"
          :socket="socket"
          :can-sit="canSit"
          :can-order="canOrder"
          :loading="socket.loading && !socket.state"
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

      <!-- The same tape deck as the study room's Wall (one shared player, so
      a tape plays on through focus mode and other pages). It sits under the
      room, where its popover opens over the floor. -->
      <CassettePlayer
        v-if="music.available"
        :music="music"
        class="mx-auto w-full max-w-xs"
        data-testid="cafe-music"
      />

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
            寫便利貼
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
          :seated="socket.heldSeatCode !== null"
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

      <ActionBanner
        v-if="canSit"
        :visible="socket.heldSeatCode !== null"
        :timer="timer"
        :verbs="cafe.verbs"
        :subjects="cafe.subjects"
        :client-config="cafe.timerConfig"
      >
        <CoffeeTray
          :coffee="socket.mySeat()?.coffee ?? null"
          :balance="acorns.balance"
          :busy="trayBusy"
          :error-message="trayError"
          :grace-minutes="graceMinutes"
          @sip="sip"
          @reorder="reorder"
          @put-away="putAway"
          @open-menu="openMenu"
        />
      </ActionBanner>

      <FocusMode
        v-if="canSit"
        :sky="sky"
        :timer="timer"
        :profile="profile"
        :music="music"
        :coffee="coffeeOnTable"
      />
    </div>
  </AppLayout>
</template>
