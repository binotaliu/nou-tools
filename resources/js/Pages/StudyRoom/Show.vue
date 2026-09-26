<script setup>
// Per AGENTS.md "自習室 (Study Room)" and
// .github/skills/laravel-best-practices/rules/inertia-vue-views.md, this
// page uses Inertia only for the page shell/navigation — the props below
// are static/semi-static (hasSchedule, emoji choices, subjects/verbs,
// client config, the viewer's own profile). The live seat map / timer /
// session state is fetched from GET /study-room/state on mount and kept in
// sync via the existing REST endpoints and Echo/Reverb broadcasts — never
// through an Inertia prop or router.reload().
import {
  computed,
  nextTick,
  onMounted,
  onUnmounted,
  reactive,
  ref,
  watch,
} from 'vue'
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
import AccessKeys from '../../Components/StudyRoom/AccessKeys.vue'
import ActionBanner from '../../Components/StudyRoom/ActionBanner.vue'
import FocusMode from '../../Components/StudyRoom/FocusMode.vue'
import Modal from '../../Components/StudyRoom/Modal.vue'
import PersonalInfoForm from '../../Components/StudyRoom/PersonalInfoForm.vue'
import RoomToolbar from '../../Components/StudyRoom/RoomToolbar.vue'
import SeatList from '../../Components/StudyRoom/SeatList.vue'
import TableChair from '../../Components/StudyRoom/TableChair.vue'
import Wall from '../../Components/StudyRoom/Wall.vue'
import FloorSkeleton from '../../Components/StudyRoom/FloorSkeleton.vue'
import LiveAnnouncer from '../../Components/StudyRoom/LiveAnnouncer.vue'
import useSeatGrid from '../../Composables/useSeatGrid'
import useSeatRovingFocus from '../../Composables/useSeatRovingFocus'
import useStudyRoomAnnouncer from '../../Composables/useStudyRoomAnnouncer'
import useStudyRoomAnnouncements from '../../Composables/useStudyRoomAnnouncements'
import useStudyRoomDemo from '../../Composables/useStudyRoomDemo'
import useStudyRoomMusic from '../../Composables/useStudyRoomMusic'
import useStudyRoomProfile from '../../Composables/useStudyRoomProfile'
import usePushSubscription from '../../Composables/usePushSubscription'
import useStudyRoomSky from '../../Composables/useStudyRoomSky'
import useStudyRoomSocket from '../../Composables/useStudyRoomSocket'
import useStudyRoomVoiceSettings from '../../Composables/useStudyRoomVoiceSettings'
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
  vapidPublicKey: { type: String, default: null },
})

// Visitors without a schedule get a preview of the room filled with fictional
// occupants (useStudyRoomDemo) instead of the live state, which they could not
// act on anyway. It has the socket's shape, so the markup below is shared.
const socket = props.hasSchedule
  ? useStudyRoomSocket(props.clientConfig)
  : useStudyRoomDemo(props.clientConfig)
const grid = useSeatGrid(socket, props.clientConfig)
const roving = useSeatRovingFocus(socket)
const sky = useStudyRoomSky(props.clientConfig)
const profile = useStudyRoomProfile(props.profile, props.emojiChoices)
const music = useStudyRoomMusic()
const announcer = useStudyRoomAnnouncer()

// Only `supported` and `enable` are used. `disable()` would call
// subscription.unsubscribe(), and a browser holds one subscription that the
// class-starting reminders share, so switching study-room notifications off
// only clears the profile flag.
// Wrapped in reactive() because it is handed to PersonalInfoForm as a prop,
// where a plain object of refs would not be unwrapped (`push.busy` would be
// an always-truthy Ref).
const push = reactive(
  usePushSubscription({
    vapidPublicKey: props.vapidPublicKey,
    subscribeUrl: '/study-room/push-subscriptions',
  })
)
const timer = useStudyTimer(
  socket,
  props.clientConfig,
  props.profile.pomodoroCycle,
  profile,
  props.verbs,
  props.subjects
)

const voiceSettings = useStudyRoomVoiceSettings()

useStudyRoomAnnouncements({ socket, timer, announcer, settings: voiceSettings })

// Errors are shown on screen and also spoken, since a failed seat claim or
// timer action otherwise leaves a screen-reader user waiting for nothing.
watch(
  () => [socket.errorMessage, timer.errorMessage],
  ([seatError, timerError], [previousSeatError, previousTimerError]) => {
    if (seatError && seatError !== previousSeatError) {
      announcer.say(seatError, { assertive: true })
    }

    if (timerError && timerError !== previousTimerError) {
      announcer.say(timerError, { assertive: true })
    }
  }
)

// Derived client-side, not from the initial server prop: the profile
// updates in place (see useStudyRoomProfile.submitProfile), so once the
// viewer has a nickname, they no longer need the profile prompt.
const needsProfile = computed(() => props.hasSchedule && !profile.nickname)

// 平面圖 or 清單, remembered per browser. Storage may be unavailable, in
// which case the map is shown and the choice lasts for the page.
const VIEW_KEY = 'nou:study-room:view:v1'

function readView() {
  try {
    return localStorage.getItem(VIEW_KEY) === 'list' ? 'list' : 'map'
  } catch {
    return 'map'
  }
}

const view = ref(readView())

watch(view, value => {
  try {
    localStorage.setItem(VIEW_KEY, value)
  } catch {
    // Storage blocked: the choice just doesn't outlive the page.
  }
})

// Taking a seat in the preview points at the sign-up banner instead.
const signUpBanner = ref(null)
const signUpHeading = ref(null)
const signUpHighlighted = ref(false)
let signUpHighlightHandle = null

watch(
  () => socket.promptOpen,
  open => {
    if (!open) {
      return
    }

    socket.promptOpen = false
    signUpHighlighted.value = true
    signUpBanner.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    // Keyboard and screen-reader users land on the banner's heading too,
    // instead of being left on a seat that did nothing.
    signUpHeading.value?.focus({ preventScroll: true })
    clearTimeout(signUpHighlightHandle)
    signUpHighlightHandle = setTimeout(() => {
      signUpHighlighted.value = false
    }, 2500)
  }
)

// Where focus goes when your own seat changes hands. Taking a seat moves
// into the control panel (a disabled seat would otherwise drop focus);
// leaving returns to the seat itself, and a release to 快速入座.
// A release only takes focus back from the panel or <body>, never from
// something the viewer is busy with elsewhere on the page.
function focusControlPanel() {
  const target =
    document.querySelector(
      '[data-testid="study-room-banner-expand"]:not([style*="display: none"])'
    ) ||
    document.querySelector(
      '[data-testid="study-room-verb-group"] input:checked'
    ) ||
    document.querySelector('[data-testid="study-room-control-panel-heading"]')

  target?.focus()
}

// Pressing your own seat (map or list) opens the panel if it's minimized and
// lands on its main control: the activity choice before a timer starts,
// otherwise whichever of 繼續/next round/暫停/break is showing (暫停 before
// break, since a running pomodoro offers both).
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

  // The sr-only heading always has a rect, so it is the last resort.
  for (const selector of OWN_SEAT_FOCUS) {
    const target = document.querySelector(selector)

    if (target && target.closest('[style*="display: none"]') === null) {
      target.focus()
      return
    }
  }
}

grid.onOwnSeatActivated(() => openControlPanel())

socket.onSeatEvent(async ({ type, code }) => {
  const seat = socket.allSeats().find(candidate => candidate.code === code)
  const label = seat ? seat.label : ''

  if (type === 'taken') {
    announcer.say('已入座 ' + label)
    await nextTick()
    focusControlPanel()
    return
  }

  const panel = document.querySelector(
    '[data-testid="study-room-control-panel"]'
  )
  const focusWasOnPanel =
    document.activeElement === document.body ||
    !!panel?.contains(document.activeElement)

  if (type === 'left') {
    announcer.say('已離開座位')
  } else {
    announcer.say('你的座位（' + label + '）因為閒置已被釋放', {
      assertive: true,
    })
  }

  if (type === 'left') {
    roving.focusSeat(code)
  } else if (focusWasOnPanel) {
    await nextTick()
    document.querySelector('[data-testid="study-room-quick-seat"]')?.focus()
  }
})

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

// VoiceOver reads an <img> as 「圖像」 whatever its alt says, so every emoji
// image is hidden from screen readers and followed by an sr-only twin that
// reads like the plain character did. The twin carries the character in
// `data-emoji-text`, spoken through CSS generated content (app.css), so the
// next parse has no text node to turn into yet another image.
function speakEmojiAsText(root) {
  for (const image of root.querySelectorAll('img.emoji:not([aria-hidden])')) {
    const twin = document.createElement('span')
    twin.className = 'sr-only'
    twin.dataset.emojiText = image.alt
    image.setAttribute('aria-hidden', 'true')
    image.after(twin)
  }
}

// Twemoji's own fallback when an image fails to load puts the character
// back as text, which would then be spoken twice alongside its twin.
function restoreEmojiText() {
  if (this.nextSibling?.dataset?.emojiText !== undefined) {
    this.nextSibling.remove()
  }

  this.replaceWith(this.alt)
}

function parseTwemoji(root) {
  if (typeof window.twemoji !== 'undefined') {
    window.twemoji.parse(root, { onerror: restoreEmojiText })
    speakEmojiAsText(root)
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
  music.load()

  document.addEventListener('visibilitychange', handleVisibilityChange)

  // Test-only bridge: tests/Browser/StudyRoomTest.php reaches into these
  // internals directly for low-level assertions (shader sampling, clock
  // overrides, injecting a realtime delta without a live Echo connection).
  // Exposed unconditionally since browser tests run against a built
  // (production) bundle. Never read by production code.
  window.__studyRoomTest = {
    socket,
    timer,
    sky,
    profile,
    grid,
    music,
    announcer,
    roving,
    voiceSettings,
  }

  // Separate Vite entry (see resources/js/echo.js) so pages that don't need
  // realtime don't pay for pusher-js/laravel-echo — dynamically imported
  // here instead of loaded globally, since it's only needed on this page.
  // The preview has nothing to subscribe to.
  if (props.hasSchedule) {
    import('../../echo')
  }

  await socket.load()

  const root = document.querySelector('[data-testid="' + rootTestidValue + '"]')

  if (root) {
    loadTwemoji(root)
  }
})

onUnmounted(() => {
  sky.stopClock()
  socket.stop()
  music.dispose()
  clearTimeout(signUpHighlightHandle)
  document.removeEventListener('visibilitychange', handleVisibilityChange)

  if (twemojiObserver) {
    twemojiObserver.disconnect()
    twemojiObserver = null
  }
})
</script>

<template>
  <Head title="自習室 - NOU 小幫手" />

  <AppLayout :reserve-bottom-space="Boolean(socket.heldSeatCode)">
    <div class="mx-auto max-w-6xl space-y-6" data-testid="study-room-page">
      <div
        class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
      >
        <div class="space-y-2">
          <h2
            class="flex items-center gap-2 text-3xl font-bold text-theme-900 dark:text-zinc-100"
          >
            自習室
            <span
              v-if="!hasSchedule"
              class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
              >預覽</span
            >
          </h2>
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            找個座位跟其他同學一起用功。自習室 {{ openHoursLabel }} 開放。
          </p>
        </div>

        <div
          v-if="socket.state && hasSchedule"
          class="inline-flex items-center gap-2 self-start rounded-full bg-theme-100 px-4 py-2 text-sm font-medium text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
          data-testid="study-room-site-total"
        >
          <FireIcon class="size-4 shrink-0" />
          <span
            >今天大家一起專注了
            {{
              timer.formatDurationLabel(
                socket.state.totals.siteFocusSecondsToday
              )
            }}</span
          >
        </div>
      </div>

      <div
        v-if="!hasSchedule"
        ref="signUpBanner"
        data-testid="study-room-needs-schedule"
        class="rounded-lg border border-theme-200 bg-white p-5 shadow-sm transition dark:border-zinc-700 dark:bg-zinc-900"
        :class="
          signUpHighlighted ? 'ring-2 ring-amber-400 dark:ring-amber-500' : ''
        "
      >
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-start gap-3">
            <TableCellsIcon
              class="mt-0.5 size-8 shrink-0 text-theme-700 dark:text-zinc-400"
            />
            <div class="space-y-1">
              <h3
                ref="signUpHeading"
                tabindex="-1"
                class="text-lg font-semibold text-theme-800 focus:outline-none dark:text-zinc-200"
                data-testid="study-room-needs-schedule-heading"
              >
                這是自習室的預覽
              </h3>
              <p class="text-sm text-theme-700 dark:text-zinc-400">
                座位上的同學都是虛構的。建立課表後就能入座，自習室會用你的課表列出「你在讀什麼」的選項。
              </p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
            <Link
              href="/schedules/create"
              class="inline-flex items-center gap-1.5 rounded-lg bg-theme-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-theme-900 dark:bg-theme-600 dark:hover:bg-theme-500"
              data-testid="study-room-create-schedule"
            >
              <PlusIcon class="size-4" />
              建立我的課表
            </Link>
            <Link
              href="/schedules/my"
              class="inline-flex items-center gap-1.5 rounded-lg border border-theme-300 bg-white px-4 py-2 text-sm font-semibold text-theme-800 transition hover:bg-theme-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
              data-testid="study-room-find-schedule"
            >
              以前建立過？找回課表
            </Link>
          </div>
        </div>
      </div>

      <LiveAnnouncer :announcer="announcer" />

      <AccessKeys
        :socket="socket"
        :timer="timer"
        :roving="roving"
        :announcer="announcer"
      />

      <div class="space-y-4" :data-testid="rootTestidValue">
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
          :music="music"
          :demo="!hasSchedule"
          :announcement-html="announcementHtml"
          :your-focus-seconds-today="
            socket.state ? socket.state.totals.yourFocusSecondsToday : 0
          "
        />

        <Modal
          v-if="hasSchedule"
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
            :push="push"
          />
        </Modal>

        <Modal
          v-if="hasSchedule"
          :open="profile.statsOpen"
          title="專注紀錄與統計"
          max-width="max-w-lg"
          teleport-to="#study-room-modal-target"
          data-testid="study-room-stats-modal"
          @close="profile.statsOpen = false"
        >
          <p
            v-show="profile.statsLoading"
            class="text-sm text-theme-700 dark:text-zinc-400"
          >
            載入中…
          </p>

          <div v-show="!profile.statsLoading" class="space-y-4">
            <div>
              <h4
                class="mb-3 text-sm font-semibold text-theme-900 dark:text-zinc-100"
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
                      ? 'bg-theme-100 dark:bg-zinc-800'
                      : 'hover:bg-theme-50 dark:hover:bg-zinc-800/60'
                  "
                  :data-testid="'study-room-stats-bar-' + day.date"
                  @click="profile.selectStatsDate(day.date)"
                >
                  <span class="flex h-20 w-full items-end justify-center">
                    <span
                      class="w-4 rounded-t-sm transition-all"
                      :class="
                        profile.selectedStatsDate === day.date
                          ? 'bg-theme-600 dark:bg-theme-400'
                          : 'bg-theme-300 dark:bg-zinc-600'
                      "
                      :style="profile.statsBarHeightStyle(day)"
                    ></span>
                  </span>
                  <span
                    class="text-xs font-medium text-theme-700 dark:text-zinc-400"
                    >{{ day.label }}</span
                  >
                </button>
              </div>
            </div>

            <div class="border-t border-theme-200 pt-4 dark:border-zinc-700">
              <div class="mb-2 flex items-center justify-between">
                <h4
                  class="text-sm font-semibold text-theme-900 dark:text-zinc-100"
                >
                  {{ profile.selectedStatsDay().label }} 的紀錄
                </h4>
                <span class="text-xs text-theme-700 dark:text-zinc-400">{{
                  profile.formatDurationLabel(
                    profile.selectedStatsDay().focusSeconds
                  )
                }}</span>
              </div>

              <p
                v-show="profile.selectedStatsDay().sessions.length === 0"
                class="text-sm text-theme-700 dark:text-zinc-400"
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
                  <span class="truncate text-theme-800 dark:text-zinc-200">{{
                    session.activityLabel
                  }}</span>
                  <span class="shrink-0 text-theme-700 dark:text-zinc-400">{{
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
          class="rounded-lg border border-theme-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            請先設定暱稱與表情符號，才能加入自習室。
          </p>
        </div>

        <FloorSkeleton
          v-if="!needsProfile && !socket.state"
          :solo-seats-per-floor="clientConfig.soloSeatsPerFloor"
          :tables-per-floor="clientConfig.tablesPerFloor"
          :seats-per-table="clientConfig.seatsPerTable"
        />

        <!-- A landmark, so VoiceOver's rotor and other landmark lists can jump
        straight to the seats (with their toolbar) past the wall and board. -->
        <section
          v-show="!needsProfile && socket.state"
          class="space-y-6"
          aria-labelledby="study-room-seats-heading"
          data-testid="study-room-seats"
        >
          <h3 id="study-room-seats-heading" class="sr-only">座位表</h3>

          <RoomToolbar
            v-model:view="view"
            :socket="socket"
            :grid="grid"
            :announcer="announcer"
            :voice-settings="hasSchedule ? voiceSettings : null"
          />

          <SeatList
            v-if="view === 'list'"
            :socket="socket"
            :grid="grid"
            :timer="timer"
          />

          <template v-else>
            <p id="study-room-seat-keys-hint" class="sr-only">
              用左右方向鍵逐一移動座位，上下方向鍵移到上一排或下一排，Home、End
              移到這層第一個或最後一個座位。
            </p>

            <section
              v-for="floor in socket.state ? socket.state.floors : []"
              :key="floor.floor"
              class="space-y-3"
              :aria-labelledby="'study-room-floor-heading-' + floor.floor"
              :data-testid="'study-room-floor-' + floor.floor"
            >
              <div class="flex items-end justify-between px-1">
                <h4
                  :id="'study-room-floor-heading-' + floor.floor"
                  class="flex items-center gap-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
                >
                  <span>{{ floor.label }}</span>
                  <span
                    class="text-sm font-normal text-theme-700 dark:text-zinc-400"
                    >閱覽室</span
                  >
                </h4>
                <span
                  class="inline-flex items-center gap-1.5 rounded-full bg-theme-100 px-2.5 py-1 text-xs text-theme-700 tabular-nums dark:bg-zinc-800 dark:text-zinc-300"
                >
                  <span class="size-1.5 rounded-full bg-emerald-500"></span>
                  <span
                    >{{ floor.occupiedCount }} /
                    {{ floor.totalCount }} 人在座</span
                  >
                </span>
              </div>

              <p
                v-if="grid.stairSpokenHint(floor)"
                class="sr-only"
                :data-testid="'study-room-floor-' + floor.floor + '-stair-hint'"
              >
                {{ grid.stairSpokenHint(floor) }}
              </p>

              <div
                class="relative rounded-2xl border-[6px] border-theme-300 bg-theme-100/60 shadow-sm dark:border-zinc-600 dark:bg-zinc-900"
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
                </template>

                <div
                  role="group"
                  :aria-label="floor.label + '座位'"
                  aria-describedby="study-room-seat-keys-hint"
                  :data-testid="'study-room-floor-' + floor.floor + '-seats'"
                  class="relative space-y-6 rounded-[10px] bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(0,0,0,0.04)_5.5rem_calc(5.5rem+1px))] px-4 pt-6 pb-16 sm:px-8 dark:bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(255,255,255,0.05)_5.5rem_calc(5.5rem+1px))]"
                  @keydown="roving.onKeydown($event, floor)"
                  @focusin="roving.onFocusIn($event, floor)"
                >
                  <div
                    class="grid grid-cols-3 justify-items-center gap-x-3 gap-y-7 sm:grid-cols-4 sm:gap-x-5 md:grid-cols-6"
                    data-testid="study-room-solo-seats"
                  >
                    <button
                      v-for="seat in floor.soloSeats"
                      :key="seat.code"
                      type="button"
                      :aria-disabled="
                        grid.isSeatActionable(seat) ? null : 'true'
                      "
                      :tabindex="roving.tabIndexFor(floor, seat)"
                      :class="grid.seatClasses(seat)"
                      :data-testid="grid.seatTestId(seat)"
                      :data-seat-code="seat.code"
                      :aria-label="
                        grid.seatAriaLabel(seat, timer.spokenTimerLabel(seat))
                      "
                      @click="grid.activateSeat(seat)"
                    >
                      <span
                        class="pointer-events-none absolute inset-x-1.5 top-0 h-4 rounded-b-md bg-theme-200 shadow-[inset_0_-2px_0_var(--color-theme-300)] dark:bg-zinc-700 dark:shadow-[inset_0_-2px_0_var(--color-zinc-600)]"
                        aria-hidden="true"
                      >
                        <span
                          class="absolute top-1 right-1.5 size-2 rounded-full transition"
                          :class="
                            seat.isOccupied
                              ? 'bg-amber-400 shadow-[0_0_8px_3px_rgba(251,191,36,0.55)]'
                              : 'bg-theme-300 dark:bg-zinc-600'
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
                          class="flex size-8 items-center justify-center rounded-lg border-2 border-b-4 border-theme-300 bg-white text-[0.625rem] font-medium text-theme-700 transition group-hover:border-theme-400 group-hover:text-theme-800 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                          >{{ seat.seatNumber }}</span
                        >
                        <span
                          class="text-[0.625rem] text-theme-700 opacity-0 transition group-hover:opacity-100 dark:text-zinc-400"
                          >點擊入座</span
                        >
                      </div>
                      <div
                        v-else
                        class="flex w-full flex-col items-center gap-0.5"
                      >
                        <div
                          v-if="grid.thoughtBubbleText(seat)"
                          class="pointer-events-none absolute -top-6 left-1/2 z-10 flex w-24 -translate-x-1/2 overflow-hidden rounded-full border border-theme-200 bg-white px-2 py-0.5 shadow-sm dark:border-zinc-600 dark:bg-zinc-800"
                          data-testid="study-room-seat-bubble"
                        >
                          <span
                            v-if="grid.needsMarquee(seat)"
                            class="flex animate-marquee text-[0.5625rem] whitespace-nowrap text-theme-700 dark:text-zinc-200"
                          >
                            <span class="pr-4">{{
                              grid.thoughtBubbleText(seat)
                            }}</span>
                            <span class="pr-4" aria-hidden="true">{{
                              grid.thoughtBubbleText(seat)
                            }}</span>
                          </span>
                          <span
                            v-else
                            class="block w-full truncate text-center text-[0.5625rem] whitespace-nowrap text-theme-700 dark:text-zinc-200"
                            >{{ grid.thoughtBubbleText(seat) }}</span
                          >
                        </div>

                        <span class="relative">
                          <span
                            class="flex size-8 items-center justify-center rounded-lg border-2 border-b-4 border-theme-400 bg-white text-lg leading-none shadow-sm dark:border-zinc-500 dark:bg-zinc-800"
                            >{{ seat.emoji }}</span
                          >
                          <span
                            v-show="grid.isMine(seat)"
                            class="absolute -top-1.5 -right-2 rounded-full bg-amber-500 px-1 text-[0.5625rem] leading-4 font-semibold text-white shadow-sm"
                            >你</span
                          >
                        </span>
                        <span
                          class="max-w-full truncate text-[0.625rem] font-medium text-theme-800 dark:text-zinc-200"
                          >{{ seat.nickname }}</span
                        >
                        <span
                          class="font-mono text-[0.625rem] tabular-nums"
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
                      role="group"
                      :aria-label="table.label"
                      :data-testid="'study-room-table-' + table.groupCode"
                    >
                      <div class="flex gap-4">
                        <TableChair
                          v-for="seat in grid.tableSeatsRow(table, 0)"
                          :key="seat.code"
                          :seat="seat"
                          backrest="border-t-4"
                          timer-side="top"
                          :grid="grid"
                          :timer="timer"
                          :roving-tabindex="roving.tabIndexFor(floor, seat)"
                        />
                      </div>

                      <div
                        aria-hidden="true"
                        class="flex h-14 w-44 items-center justify-center gap-2 rounded-xl border-2 border-theme-300 bg-theme-200 shadow-[inset_0_2px_0_rgba(255,255,255,0.6),0_2px_4px_rgba(0,0,0,0.06)] dark:border-zinc-600 dark:bg-zinc-700 dark:shadow-none"
                      >
                        <span class="text-base leading-none" aria-hidden="true"
                          >🪴</span
                        >
                        <span
                          class="text-xs font-medium text-theme-800 dark:text-zinc-300"
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
                          :grid="grid"
                          :timer="timer"
                          :roving-tabindex="roving.tabIndexFor(floor, seat)"
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
                        class="max-w-16 text-xs leading-tight text-theme-700 dark:text-zinc-400"
                        aria-hidden="true"
                        >{{ grid.stairHint(floor) }}</span
                      >
                      <div
                        class="relative h-9 w-16 overflow-hidden rounded-t-sm border-x-2 border-t-2 border-theme-300 bg-[repeating-linear-gradient(180deg,var(--color-theme-100)_0_5px,var(--color-theme-300)_5px_6px)] dark:border-zinc-600 dark:bg-[repeating-linear-gradient(180deg,var(--color-zinc-800)_0_5px,var(--color-zinc-600)_5px_6px)]"
                        aria-hidden="true"
                      >
                        <ArrowUpIcon
                          v-if="!grid.isStairBlocked(floor)"
                          class="absolute top-1/2 left-1/2 size-4 -translate-x-1/2 -translate-y-1/2 text-theme-700 dark:text-zinc-300"
                        />
                        <div
                          v-else
                          class="absolute inset-x-1.5 top-1/2 flex -translate-y-1/2 flex-col items-center gap-1"
                          data-testid="study-room-stair-blocked"
                        >
                          <div
                            class="h-1.5 w-full rounded-full bg-theme-400/80 shadow-sm dark:bg-zinc-500/80"
                          ></div>
                          <LockClosedIcon
                            class="size-3.5 text-theme-700 dark:text-zinc-400"
                          />
                          <div
                            class="h-1.5 w-full rounded-full bg-theme-400/80 shadow-sm dark:bg-zinc-500/80"
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
                        class="max-w-16 text-xs leading-tight text-theme-700 dark:text-zinc-400"
                        aria-hidden="true"
                        >{{ grid.stairDownHint(floor) }}</span
                      >
                      <div
                        class="relative h-9 w-16 rounded-b-sm border-x-2 border-b-2 border-theme-300 bg-[repeating-linear-gradient(180deg,var(--color-theme-100)_0_5px,var(--color-theme-300)_5px_6px)] dark:border-zinc-600 dark:bg-[repeating-linear-gradient(180deg,var(--color-zinc-800)_0_5px,var(--color-zinc-600)_5px_6px)]"
                        aria-hidden="true"
                      >
                        <ArrowDownIcon
                          class="absolute top-1/2 left-1/2 size-4 -translate-x-1/2 -translate-y-1/2 text-theme-700 dark:text-zinc-300"
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
                      class="h-4 w-full max-w-md rounded-t-sm border-x-2 border-t-2 border-theme-300 bg-[repeating-linear-gradient(90deg,var(--color-theme-500)_0_5px,var(--color-theme-50)_5px_6px,var(--color-theme-700)_6px_9px,var(--color-theme-50)_9px_10px,var(--color-sky-600)_10px_14px,var(--color-theme-50)_14px_15px,var(--color-emerald-600)_15px_21px,var(--color-theme-50)_21px_22px,var(--color-theme-400)_22px_25px,var(--color-theme-50)_25px_26px)] opacity-70 dark:border-zinc-600 dark:opacity-50"
                    ></div>
                  </div>
                </div>
              </div>
            </section>

            <ul
              class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-xs text-theme-700 dark:text-zinc-400"
            >
              <li class="inline-flex items-center gap-1.5">
                <span
                  class="size-3 rounded-full border-2 border-b-[3px] border-theme-300 bg-white dark:border-zinc-600 dark:bg-zinc-800"
                ></span>
                空位
              </li>
              <li class="inline-flex items-center gap-1.5">
                <span
                  class="size-3 rounded-full bg-amber-400 shadow-[0_0_6px_2px_rgba(251,191,36,0.5)]"
                ></span>
                有人（檯燈亮著）
              </li>
              <li class="inline-flex items-center gap-1.5">
                <span
                  class="rounded-full bg-amber-500 px-1 text-xs leading-4 font-semibold text-white"
                  >你</span
                >
                你的座位
              </li>
            </ul>
          </template>
        </section>

        <ActionBanner
          v-if="hasSchedule"
          :visible="!!socket.heldSeatCode"
          :timer="timer"
          :verbs="verbs"
          :subjects="subjects"
          :client-config="clientConfig"
        />

        <FocusMode
          v-if="hasSchedule"
          :sky="sky"
          :timer="timer"
          :profile="profile"
          :music="music"
        />
      </div>
    </div>
  </AppLayout>
</template>
