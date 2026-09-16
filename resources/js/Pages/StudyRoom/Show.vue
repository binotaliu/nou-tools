<script setup>
// Per AGENTS.md "自習室 (Study Room)" and
// .github/skills/laravel-best-practices/rules/inertia-vue-views.md, this
// page uses Inertia only for the page shell/navigation — the props below
// are static/semi-static (hasSchedule, emoji choices, subjects/verbs,
// client config, the viewer's own profile). The live seat map / timer /
// session state is fetched from GET /study-room/state on mount and kept in
// sync via the existing REST endpoints and Echo/Reverb broadcasts — never
// through an Inertia prop or router.reload().
import { computed, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowUpIcon,
  ArrowDownIcon,
  ExclamationTriangleIcon,
  FireIcon,
  LockClosedIcon,
  PlusIcon,
  TableCellsIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '../../Layouts/AppLayout.vue'
import ActionBanner from '../../Components/StudyRoom/ActionBanner.vue'
import FocusMode from '../../Components/StudyRoom/FocusMode.vue'
import Modal from '../../Components/StudyRoom/Modal.vue'
import PersonalInfoForm from '../../Components/StudyRoom/PersonalInfoForm.vue'
import TableChair from '../../Components/StudyRoom/TableChair.vue'
import Wall from '../../Components/StudyRoom/Wall.vue'
import FloorSkeleton from '../../Components/StudyRoom/FloorSkeleton.vue'
import useSeatGrid from '../../Composables/useSeatGrid'
import useStudyRoomProfile from '../../Composables/useStudyRoomProfile'
import useStudyRoomSky from '../../Composables/useStudyRoomSky'
import useStudyRoomSocket from '../../Composables/useStudyRoomSocket'
import useStudyTimer from '../../Composables/useStudyTimer'

const props = defineProps({
  hasSchedule: { type: Boolean, required: true },
  needsProfile: { type: Boolean, required: true },
  emojiChoices: { type: Array, required: true },
  announcementHtml: { type: String, required: true },
  openHoursLabel: { type: String, required: true },
  isOpen: { type: Boolean, required: true },
  subjects: { type: Array, required: true },
  verbs: { type: Array, required: true },
  profile: { type: Object, required: true },
  clientConfig: { type: Object, required: true },
})

const socket = useStudyRoomSocket(props.clientConfig)
const grid = useSeatGrid(socket, props.clientConfig)
const sky = useStudyRoomSky(props.clientConfig)
const profile = useStudyRoomProfile(props.profile, props.emojiChoices)
const timer = useStudyTimer(
  socket,
  props.clientConfig,
  props.profile.pomodoroCycle,
  profile,
  props.verbs,
  props.subjects
)

// Derived client-side, not from the initial server prop: the profile
// updates in place (see useStudyRoomProfile.submitProfile), so once the
// viewer has a nickname, they no longer need the profile prompt.
const needsProfile = computed(() => !profile.nickname)

socket.onStateChange(() => {
  timer.onRoomStateChanged(() => sky.unmountSkyCanvas('focus'))
})

function handleVisibilityChange() {
  timer.updateTabIndicators()

  if (!document.hidden) {
    timer.suppressAlreadyFinishedSound()
    socket.refresh()
    socket.heartbeat()
  }
}

// twemoji renders emoji consistently across platforms. It's dynamically
// imported rather than loaded globally, since it's only needed on this page
// — mirrors the echo.js import below.
let twemojiObserver = null
let twemojiRafHandle = null

function parseTwemoji(root) {
  if (typeof window.twemoji !== 'undefined') {
    window.twemoji.parse(root)
  }
}

function scheduleTwemojiParse(root) {
  if (twemojiRafHandle) {
    return
  }

  twemojiRafHandle = requestAnimationFrame(() => {
    twemojiRafHandle = null
    parseTwemoji(root)
  })
}

async function loadTwemoji(root) {
  if (window.twemoji) {
    startTwemojiObserver(root)
    return
  }

  const { default: twemoji } = await import('@twemoji/api')
  window.twemoji = twemoji
  startTwemojiObserver(root)
}

function startTwemojiObserver(root) {
  parseTwemoji(root)

  twemojiObserver = new MutationObserver(() => scheduleTwemojiParse(root))
  twemojiObserver.observe(root, {
    childList: true,
    subtree: true,
    characterData: true,
  })
}

const rootTestidValue = 'study-room-root'

onMounted(async () => {
  if (needsProfile.value) {
    profile.openPersonalInfo()
  }

  timer.initTabIndicators()
  sky.startClock()
  socket.start()

  document.addEventListener('visibilitychange', handleVisibilityChange)

  // Test-only bridge: tests/Browser/StudyRoomTest.php reaches into these
  // internals directly for low-level assertions (shader sampling, clock
  // overrides, injecting a realtime delta without a live Echo connection).
  // Exposed unconditionally since browser tests run against a built
  // (production) bundle. Never read by production code.
  window.__studyRoomTest = { socket, timer, sky, profile, grid }

  // Separate Vite entry (see resources/js/echo.js) so pages that don't need
  // realtime don't pay for pusher-js/laravel-echo — dynamically imported
  // here instead of loaded globally, since it's only needed on this page.
  import('../../echo')

  await socket.load()

  const root = document.querySelector('[data-testid="' + rootTestidValue + '"]')

  if (root) {
    loadTwemoji(root)
  }
})

onUnmounted(() => {
  sky.stopClock()
  socket.stop()
  document.removeEventListener('visibilitychange', handleVisibilityChange)

  if (twemojiObserver) {
    twemojiObserver.disconnect()
    twemojiObserver = null
  }
})
</script>

<template>
  <Head title="自習室 - NOU 小幫手">
    <meta name="description" content="24 小時開放的自習室，歡迎一起用功。" />
  </Head>

  <AppLayout>
    <div class="mx-auto max-w-6xl space-y-6" data-testid="study-room-page">
      <div
        class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
      >
        <div class="space-y-2">
          <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
            自習室
          </h2>
          <p class="text-sm text-warm-600 dark:text-zinc-400">
            找個座位跟其他同學一起用功。自習室 {{ openHoursLabel }} 開放。
          </p>
        </div>

        <div
          v-if="socket.state"
          class="inline-flex items-center gap-2 self-start rounded-full bg-warm-100 px-4 py-2 text-sm font-medium text-warm-800 dark:bg-zinc-800 dark:text-zinc-200"
          data-testid="study-room-site-total"
        >
          <FireIcon class="size-4 shrink-0" />
          今天大家一起專注了
          <span>{{
            timer.formatDurationLabel(socket.state.totals.siteFocusSecondsToday)
          }}</span>
        </div>
      </div>

      <div
        v-if="!hasSchedule"
        data-testid="study-room-needs-schedule"
        class="rounded-lg border border-warm-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="flex flex-col items-center gap-3 py-6 text-center">
          <TableCellsIcon class="size-10 text-warm-400 dark:text-zinc-500" />
          <div class="space-y-1">
            <h3 class="text-xl font-semibold text-warm-800 dark:text-zinc-200">
              先建立課表才能進自習室
            </h3>
            <p class="text-sm text-warm-500 dark:text-zinc-400">
              自習室會用你的課表列出「你在讀什麼」的選項，所以需要先有一份儲存好的課表。
            </p>
          </div>
          <div class="flex flex-wrap justify-center gap-2 pt-2">
            <Link
              href="/schedules/create"
              class="inline-flex items-center gap-1.5 rounded-lg bg-warm-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-warm-900 dark:bg-warm-600 dark:hover:bg-warm-500"
            >
              <PlusIcon class="size-4" />
              建立我的課表
            </Link>
            <Link
              href="/schedules/my"
              class="inline-flex items-center gap-1.5 rounded-lg border border-warm-300 bg-white px-4 py-2 text-sm font-semibold text-warm-800 transition hover:bg-warm-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
              我已經有課表了
            </Link>
          </div>
        </div>
      </div>

      <div
        v-else
        class="space-y-4"
        :class="socket.heldSeatCode ? 'pb-96 sm:pb-72 lg:pb-48' : ''"
        :data-testid="rootTestidValue"
      >
        <div id="study-room-modal-target"></div>

        <div
          v-show="socket.connectionFailed"
          class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
          data-testid="study-room-connection-error"
        >
          <ExclamationTriangleIcon class="size-4 shrink-0" />
          <span
            >目前無法連上自習室，自習室可能正在維護。如果問題持續，請聯絡站長。</span
          >
        </div>

        <div
          v-show="socket.errorMessage"
          class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
          data-testid="study-room-error-toast"
        >
          <ExclamationTriangleIcon class="size-4 shrink-0" />
          <span>{{ socket.errorMessage }}</span>
        </div>

        <Wall
          :sky="sky"
          :profile="profile"
          :announcement-html="announcementHtml"
          :your-focus-seconds-today="
            socket.state ? socket.state.totals.yourFocusSecondsToday : 0
          "
        />

        <Modal
          :open="profile.personalInfoOpen"
          title="你的自習室資料"
          max-width="max-w-lg"
          teleport-to="#study-room-modal-target"
          data-testid="study-room-personal-info-modal"
          @close="profile.closePersonalInfo()"
        >
          <PersonalInfoForm
            :profile="profile"
            :clock-now="sky.clockNow"
            :nickname-min-length="clientConfig.nicknameMinLength"
            :nickname-max-length="clientConfig.nicknameMaxLength"
            :nickname-cooldown-days="clientConfig.nicknameCooldownDays"
          />
        </Modal>

        <Modal
          :open="profile.statsOpen"
          title="專注紀錄與統計"
          max-width="max-w-lg"
          teleport-to="#study-room-modal-target"
          data-testid="study-room-stats-modal"
          @close="profile.statsOpen = false"
        >
          <p
            v-show="profile.statsLoading"
            class="text-sm text-warm-500 dark:text-zinc-400"
          >
            載入中…
          </p>

          <div v-show="!profile.statsLoading" class="space-y-4">
            <div>
              <h4
                class="mb-3 text-sm font-semibold text-warm-900 dark:text-zinc-100"
              >
                最近 7 天
              </h4>

              <div
                class="flex items-end justify-between gap-1.5"
                data-testid="study-room-stats-chart"
              >
                <button
                  v-for="day in profile.statsDays"
                  :key="day.date"
                  type="button"
                  class="flex flex-1 flex-col items-center gap-1.5 rounded-md py-1.5 transition"
                  :class="
                    profile.selectedStatsDate === day.date
                      ? 'bg-warm-100 dark:bg-zinc-800'
                      : 'hover:bg-warm-50 dark:hover:bg-zinc-800/60'
                  "
                  :data-testid="'study-room-stats-bar-' + day.date"
                  @click="profile.selectStatsDate(day.date)"
                >
                  <span class="flex h-20 w-full items-end justify-center">
                    <span
                      class="w-4 rounded-t-sm transition-all"
                      :class="
                        profile.selectedStatsDate === day.date
                          ? 'bg-warm-600 dark:bg-warm-400'
                          : 'bg-warm-300 dark:bg-zinc-600'
                      "
                      :style="profile.statsBarHeightStyle(day)"
                    ></span>
                  </span>
                  <span
                    class="text-xs font-medium text-warm-600 dark:text-zinc-400"
                    >{{ day.label }}</span
                  >
                </button>
              </div>
            </div>

            <div class="border-t border-warm-200 pt-4 dark:border-zinc-700">
              <div class="mb-2 flex items-center justify-between">
                <h4
                  class="text-sm font-semibold text-warm-900 dark:text-zinc-100"
                >
                  {{ profile.selectedStatsDay().label }} 的紀錄
                </h4>
                <span class="text-xs text-warm-500 dark:text-zinc-400">{{
                  profile.formatDurationLabel(
                    profile.selectedStatsDay().focusSeconds
                  )
                }}</span>
              </div>

              <p
                v-show="profile.selectedStatsDay().sessions.length === 0"
                class="text-sm text-warm-500 dark:text-zinc-400"
              >
                這天沒有紀錄
              </p>

              <ul
                v-show="profile.selectedStatsDay().sessions.length > 0"
                class="space-y-2"
                data-testid="study-room-stats-session-log"
              >
                <li
                  v-for="session in profile.selectedStatsDay().sessions"
                  :key="session.startedAt"
                  class="flex items-center justify-between gap-2 text-sm"
                >
                  <span class="truncate text-warm-800 dark:text-zinc-200">{{
                    session.activityLabel
                  }}</span>
                  <span class="shrink-0 text-warm-500 dark:text-zinc-400">{{
                    profile.sessionDurationLabel(session)
                  }}</span>
                </li>
              </ul>
            </div>
          </div>
        </Modal>

        <div
          v-show="needsProfile"
          data-testid="study-room-needs-profile-placeholder"
          class="rounded-lg border border-warm-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
          <p class="text-sm text-warm-600 dark:text-zinc-400">
            請先設定暱稱與表情符號，才能加入自習室。
          </p>
        </div>

        <FloorSkeleton
          v-if="!needsProfile && !socket.state"
          :solo-seats-per-floor="clientConfig.soloSeatsPerFloor"
          :tables-per-floor="clientConfig.tablesPerFloor"
          :seats-per-table="clientConfig.seatsPerTable"
        />

        <div v-show="!needsProfile && socket.state" class="space-y-6">
          <section
            v-for="floor in socket.state ? socket.state.floors : []"
            :key="floor.floor"
            class="space-y-3"
            :data-testid="'study-room-floor-' + floor.floor"
          >
            <div class="flex items-end justify-between px-1">
              <h3
                class="flex items-center gap-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
              >
                <span>{{ floor.label }}</span>
                <span
                  class="text-sm font-normal text-warm-500 dark:text-zinc-400"
                  >閱覽室</span
                >
              </h3>
              <span
                class="inline-flex items-center gap-1.5 rounded-full bg-warm-100 px-2.5 py-1 text-xs text-warm-700 tabular-nums dark:bg-zinc-800 dark:text-zinc-300"
              >
                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                <span
                  >{{ floor.occupiedCount }} /
                  {{ floor.totalCount }} 人在座</span
                >
              </span>
            </div>

            <div
              class="relative rounded-2xl border-[6px] border-warm-300 bg-warm-100/60 shadow-sm dark:border-zinc-600 dark:bg-zinc-900"
            >
              <div
                class="pointer-events-none absolute inset-x-10 -top-[6px] z-10 flex h-[6px] gap-3 sm:inset-x-20"
                data-testid="study-room-windows"
                aria-hidden="true"
              >
                <span
                  v-for="pane in 4"
                  :key="pane"
                  class="flex-1 bg-sky-200 transition-[background] duration-1000 dark:bg-sky-900"
                  :style="sky.windowPaneStyle()"
                ></span>
              </div>
              <div
                class="pointer-events-none absolute inset-x-10 top-0 h-12 transition-[background] duration-1000 sm:inset-x-20"
                :style="sky.windowLightStyle()"
                aria-hidden="true"
              ></div>

              <template v-if="grid.isGroundFloor(floor)">
                <div
                  class="pointer-events-none absolute right-8 -bottom-[6px] z-10 h-[6px] w-12 bg-warm-50 dark:bg-zinc-950"
                  aria-hidden="true"
                ></div>
                <div
                  class="pointer-events-none absolute right-8 bottom-0 z-10 size-12 rounded-tl-full border-t border-l border-dashed border-warm-400 dark:border-zinc-500"
                  aria-hidden="true"
                >
                  <span
                    class="absolute right-0 bottom-0 h-full w-[3px] origin-bottom -rotate-[70deg] rounded-full bg-warm-500 dark:bg-zinc-400"
                  ></span>
                </div>
              </template>

              <div
                class="relative space-y-6 rounded-[10px] bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(0,0,0,0.04)_5.5rem_calc(5.5rem+1px))] px-4 pt-6 pb-16 sm:px-8 dark:bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(255,255,255,0.05)_5.5rem_calc(5.5rem+1px))]"
              >
                <div
                  class="grid grid-cols-3 justify-items-center gap-x-3 gap-y-7 sm:grid-cols-4 sm:gap-x-5 md:grid-cols-6"
                  data-testid="study-room-solo-seats"
                >
                  <button
                    v-for="seat in floor.soloSeats"
                    :key="seat.code"
                    type="button"
                    :disabled="
                      seat.isOccupied ||
                      socket.busySeatCode !== null ||
                      socket.heldSeatCode !== null
                    "
                    :class="grid.seatClasses(seat)"
                    :data-testid="grid.seatTestId(seat)"
                    :aria-label="grid.seatAriaLabel(seat)"
                    @click="socket.take(seat.code)"
                  >
                    <span
                      class="pointer-events-none absolute inset-x-1.5 top-0 h-4 rounded-b-md bg-warm-200 shadow-[inset_0_-2px_0_var(--color-warm-300)] dark:bg-zinc-700 dark:shadow-[inset_0_-2px_0_var(--color-zinc-600)]"
                      aria-hidden="true"
                    >
                      <span
                        class="absolute top-1 right-1.5 size-2 rounded-full transition"
                        :class="
                          seat.isOccupied
                            ? 'bg-amber-400 shadow-[0_0_8px_3px_rgba(251,191,36,0.55)]'
                            : 'bg-warm-300 dark:bg-zinc-600'
                        "
                      ></span>
                      <span
                        v-show="seat.isOccupied"
                        class="absolute top-1.5 left-2 h-1.5 w-4 rounded-[2px] bg-sky-400/80 dark:bg-sky-500/70"
                      ></span>
                    </span>

                    <div
                      v-if="!seat.isOccupied"
                      class="flex flex-col items-center gap-1"
                    >
                      <span
                        class="flex size-8 items-center justify-center rounded-lg border-2 border-b-4 border-warm-300 bg-white text-[10px] font-medium text-warm-400 transition group-hover:border-warm-400 group-hover:text-warm-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-500"
                        >{{ seat.seatNumber }}</span
                      >
                      <span
                        class="text-[10px] text-warm-400 opacity-0 transition group-hover:opacity-100 dark:text-zinc-500"
                        >點擊入座</span
                      >
                    </div>
                    <div
                      v-else
                      class="flex w-full flex-col items-center gap-0.5"
                    >
                      <div
                        v-if="grid.thoughtBubbleText(seat)"
                        class="pointer-events-none absolute -top-6 left-1/2 z-10 flex w-24 -translate-x-1/2 overflow-hidden rounded-full border border-warm-200 bg-white px-2 py-0.5 shadow-sm dark:border-zinc-600 dark:bg-zinc-800"
                        data-testid="study-room-seat-bubble"
                      >
                        <span
                          v-if="grid.needsMarquee(seat)"
                          class="flex animate-marquee text-[9px] whitespace-nowrap text-warm-700 dark:text-zinc-200"
                        >
                          <span class="pr-4">{{
                            grid.thoughtBubbleText(seat)
                          }}</span>
                          <span class="pr-4">{{
                            grid.thoughtBubbleText(seat)
                          }}</span>
                        </span>
                        <span
                          v-else
                          class="block w-full truncate text-center text-[9px] whitespace-nowrap text-warm-700 dark:text-zinc-200"
                          >{{ grid.thoughtBubbleText(seat) }}</span
                        >
                      </div>

                      <span class="relative">
                        <span
                          class="flex size-8 items-center justify-center rounded-lg border-2 border-b-4 border-warm-400 bg-white text-lg leading-none shadow-sm dark:border-zinc-500 dark:bg-zinc-800"
                          >{{ seat.emoji }}</span
                        >
                        <span
                          v-show="grid.isMine(seat)"
                          class="absolute -top-1.5 -right-2 rounded-full bg-amber-500 px-1 text-[9px] leading-4 font-semibold text-white shadow-sm"
                          >你</span
                        >
                      </span>
                      <span
                        class="max-w-full truncate text-[10px] font-medium text-warm-800 dark:text-zinc-200"
                        >{{ seat.nickname }}</span
                      >
                      <span
                        class="font-mono text-[10px] tabular-nums"
                        :class="grid.seatTimerLabelClass(seat)"
                        >{{ timer.timerLabel(seat) }}</span
                      >
                    </div>
                  </button>
                </div>

                <div
                  class="flex flex-wrap justify-center gap-x-8 gap-y-6 pt-2"
                  data-testid="study-room-tables"
                >
                  <div
                    v-for="table in floor.tables"
                    :key="table.groupCode"
                    class="flex flex-col items-center gap-1"
                    :data-testid="'study-room-table-' + table.groupCode"
                  >
                    <div class="flex gap-4">
                      <TableChair
                        v-for="seat in grid.tableSeatsRow(table, 0)"
                        :key="seat.code"
                        :seat="seat"
                        backrest="border-t-4"
                        timer-side="top"
                        :socket="socket"
                        :grid="grid"
                        :timer="timer"
                      />
                    </div>

                    <div
                      class="flex h-14 w-44 items-center justify-center gap-2 rounded-xl border-2 border-warm-300 bg-warm-200 shadow-[inset_0_2px_0_rgba(255,255,255,0.6),0_2px_4px_rgba(0,0,0,0.06)] dark:border-zinc-600 dark:bg-zinc-700 dark:shadow-none"
                    >
                      <span class="text-base leading-none" aria-hidden="true"
                        >🪴</span
                      >
                      <span
                        class="text-xs font-medium text-warm-700 dark:text-zinc-300"
                        >{{ table.label }}</span
                      >
                    </div>

                    <div class="flex gap-4">
                      <TableChair
                        v-for="seat in grid.tableSeatsRow(table, 1)"
                        :key="seat.code"
                        :seat="seat"
                        backrest="border-b-4"
                        timer-side="bottom"
                        :socket="socket"
                        :grid="grid"
                        :timer="timer"
                      />
                    </div>
                  </div>
                </div>

                <div
                  class="pointer-events-none absolute bottom-0 left-3 flex items-end gap-2 sm:left-5"
                  data-testid="study-room-stairs"
                >
                  <div
                    class="flex flex-col items-start gap-1"
                    data-testid="study-room-stair-up"
                  >
                    <span
                      class="max-w-16 text-[10px] leading-tight text-warm-500 dark:text-zinc-400"
                      >{{ grid.stairHint(floor) }}</span
                    >
                    <div
                      class="relative h-9 w-16 overflow-hidden rounded-t-sm border-x-2 border-t-2 border-warm-300 bg-[repeating-linear-gradient(180deg,var(--color-warm-100)_0_5px,var(--color-warm-300)_5px_6px)] dark:border-zinc-600 dark:bg-[repeating-linear-gradient(180deg,var(--color-zinc-800)_0_5px,var(--color-zinc-600)_5px_6px)]"
                      aria-hidden="true"
                    >
                      <ArrowUpIcon
                        v-if="!grid.isStairBlocked(floor)"
                        class="absolute top-1/2 left-1/2 size-4 -translate-x-1/2 -translate-y-1/2 text-warm-600 dark:text-zinc-300"
                      />
                      <div
                        v-else
                        class="absolute inset-x-1.5 top-1/2 flex -translate-y-1/2 flex-col items-center gap-1"
                        data-testid="study-room-stair-blocked"
                      >
                        <div
                          class="h-1.5 w-full rounded-full bg-warm-400/80 shadow-sm dark:bg-zinc-500/80"
                        ></div>
                        <LockClosedIcon
                          class="size-3.5 text-warm-500 dark:text-zinc-400"
                        />
                        <div
                          class="h-1.5 w-full rounded-full bg-warm-400/80 shadow-sm dark:bg-zinc-500/80"
                        ></div>
                      </div>
                    </div>
                  </div>

                  <div
                    v-if="!grid.isGroundFloor(floor)"
                    class="flex flex-col items-start gap-1"
                    data-testid="study-room-stair-down"
                  >
                    <span
                      class="max-w-16 text-[10px] leading-tight text-warm-500 dark:text-zinc-400"
                      >{{ grid.stairDownHint(floor) }}</span
                    >
                    <div
                      class="relative h-9 w-16 rounded-b-sm border-x-2 border-b-2 border-warm-300 bg-[repeating-linear-gradient(180deg,var(--color-warm-100)_0_5px,var(--color-warm-300)_5px_6px)] dark:border-zinc-600 dark:bg-[repeating-linear-gradient(180deg,var(--color-zinc-800)_0_5px,var(--color-zinc-600)_5px_6px)]"
                      aria-hidden="true"
                    >
                      <ArrowDownIcon
                        class="absolute top-1/2 left-1/2 size-4 -translate-x-1/2 -translate-y-1/2 text-warm-600 dark:text-zinc-300"
                      />
                    </div>
                  </div>
                </div>

                <div
                  class="pointer-events-none absolute right-24 bottom-0 flex h-5 items-end justify-center"
                  :class="grid.isGroundFloor(floor) ? 'left-24' : 'left-40'"
                  aria-hidden="true"
                >
                  <div
                    class="h-4 w-full max-w-md rounded-t-sm border-x-2 border-t-2 border-warm-300 bg-[repeating-linear-gradient(90deg,var(--color-warm-500)_0_5px,var(--color-warm-50)_5px_6px,var(--color-warm-700)_6px_9px,var(--color-warm-50)_9px_10px,var(--color-sky-600)_10px_14px,var(--color-warm-50)_14px_15px,var(--color-emerald-600)_15px_21px,var(--color-warm-50)_21px_22px,var(--color-warm-400)_22px_25px,var(--color-warm-50)_25px_26px)] opacity-70 dark:border-zinc-600 dark:opacity-50"
                  ></div>
                </div>
              </div>
            </div>
          </section>

          <p
            class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-xs text-warm-500 dark:text-zinc-400"
          >
            <span class="inline-flex items-center gap-1.5">
              <span
                class="size-3 rounded-full border-2 border-b-[3px] border-warm-300 bg-white dark:border-zinc-600 dark:bg-zinc-800"
              ></span>
              空位
            </span>
            <span class="inline-flex items-center gap-1.5">
              <span
                class="size-3 rounded-full bg-amber-400 shadow-[0_0_6px_2px_rgba(251,191,36,0.5)]"
              ></span>
              有人（檯燈亮著）
            </span>
            <span class="inline-flex items-center gap-1.5">
              <span
                class="rounded-full bg-amber-500 px-1 text-[9px] leading-4 font-semibold text-white"
                >你</span
              >
              你的座位
            </span>
          </p>
        </div>

        <ActionBanner
          :visible="!!socket.heldSeatCode"
          :timer="timer"
          :verbs="verbs"
          :subjects="subjects"
          :client-config="clientConfig"
        />

        <FocusMode :sky="sky" :timer="timer" :profile="profile" />
      </div>
    </div>
  </AppLayout>
</template>
