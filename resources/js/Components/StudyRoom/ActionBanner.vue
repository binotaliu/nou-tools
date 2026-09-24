<script setup>
// The control panel fixed to the bottom of the page while the viewer holds
// a seat — the "start timer" form, the running-timer countdown/controls,
// the pomodoro cycle settings modal, and the change-activity modal.
import { nextTick, onUpdated, ref } from 'vue'
import {
  ArrowRightStartOnRectangleIcon,
  ArrowsPointingOutIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  ExclamationTriangleIcon,
  PauseIcon,
  PencilSquareIcon,
  PlayIcon,
  SparklesIcon,
  StopIcon,
} from '@heroicons/vue/24/outline'
import { PlayIcon as PlaySolidIcon } from '@heroicons/vue/24/solid'
import ActivityPicker from './ActivityPicker.vue'
import DeskLamp from './DeskLamp.vue'
import Modal from './Modal.vue'

const MINIMIZED_KEY = 'nou:study-room:banner-minimized:v1'

// Minimizing leaves a slim bar so the floor map gets the screen back. It is a
// per-viewer convenience, so it lives in localStorage and the banner still
// works (expanded) when storage is unavailable.
function readMinimized() {
  try {
    return localStorage.getItem(MINIMIZED_KEY) === '1'
  } catch {
    return false
  }
}

const minimized = ref(readMinimized())

function setMinimized(value) {
  minimized.value = value

  try {
    localStorage.setItem(MINIMIZED_KEY, value ? '1' : '0')
  } catch {
    // Storage blocked: the choice just doesn't outlive the page.
  }
}

// --- keyboard focus ---
//
// Most controls here swap places with v-show (暫停 ↔ 繼續, the start form ↔
// the countdown, 收合 ↔ 展開), and hiding the focused button would drop
// focus to <body>. Each action says where focus goes next; anything that
// changes on its own (a round ending while you sit on 暫停) falls back to
// the first visible control in FALLBACK_FOCUS.
const FALLBACK_FOCUS = [
  'study-room-start-break',
  'study-room-next-round',
  'study-room-resume-timer',
  'study-room-pause-timer',
  'study-room-start-timer',
  'study-room-banner-expand',
  'study-room-stop-timer',
]

const panel = ref(null)
let lastFocusedInPanel = null

function focusFirstVisible(testIds) {
  if (!panel.value) {
    return false
  }

  for (const testId of testIds) {
    const element = panel.value.querySelector('[data-testid="' + testId + '"]')

    if (element && element.getClientRects().length > 0) {
      element.focus()
      return true
    }
  }

  return false
}

async function act(action, ...nextFocus) {
  await action()
  await nextTick()
  focusFirstVisible(nextFocus)
}

function toggleMinimized(value) {
  setMinimized(value)
  nextTick(() =>
    focusFirstVisible([
      value ? 'study-room-banner-expand' : 'study-room-banner-minimize',
    ])
  )
}

function onPanelFocusIn(event) {
  lastFocusedInPanel = event.target
}

// Leaving for another part of the page ends the panel's claim on focus; a
// focusout without a relatedTarget is the focused button being hidden.
function onPanelFocusOut(event) {
  if (event.relatedTarget && !panel.value?.contains(event.relatedTarget)) {
    lastFocusedInPanel = null
  }
}

onUpdated(() => {
  if (!lastFocusedInPanel || lastFocusedInPanel.getClientRects().length > 0) {
    return
  }

  const active = document.activeElement

  if (active === document.body || !panel.value?.contains(active)) {
    focusFirstVisible(FALLBACK_FOCUS)
  }
})

defineProps({
  visible: { type: Boolean, required: true },
  timer: { type: Object, required: true },
  verbs: { type: Array, required: true },
  subjects: { type: Array, required: true },
  clientConfig: { type: Object, required: true },
})
</script>

<template>
  <div>
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-y-full opacity-0"
      enter-to-class="translate-y-0 opacity-100"
    >
      <div
        v-if="visible"
        ref="panel"
        role="region"
        aria-labelledby="study-room-control-panel-heading"
        class="fixed inset-x-0 bottom-(--pwa-nav-height) z-40 mb-0"
        data-testid="study-room-control-panel"
        @focusin="onPanelFocusIn"
        @focusout="onPanelFocusOut"
      >
        <h3
          id="study-room-control-panel-heading"
          tabindex="-1"
          class="sr-only"
          data-testid="study-room-control-panel-heading"
        >
          你的座位：{{ timer.mySeatLabel() }}
        </h3>
        <div class="mx-auto max-w-6xl sm:px-4">
          <div
            class="relative overflow-hidden border-t-[6px] border-theme-300 bg-theme-50 bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(0,0,0,0.035)_5.5rem_calc(5.5rem+1px))] shadow-[0_-10px_40px_rgba(0,0,0,0.14)] sm:rounded-t-2xl sm:border-x-[6px] dark:border-zinc-600 dark:bg-zinc-900 dark:bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(255,255,255,0.05)_5.5rem_calc(5.5rem+1px))]"
          >
            <div
              class="pointer-events-none absolute -top-16 -left-12 size-64 rounded-full bg-amber-300/50 blur-3xl transition-opacity duration-1000 dark:bg-amber-400/25"
              :class="timer.hasTimer() ? 'opacity-100' : 'opacity-0'"
              aria-hidden="true"
            ></div>

            <button
              v-show="!minimized"
              type="button"
              class="relative flex h-5 w-full items-center justify-center text-theme-700 transition hover:text-theme-800 sm:absolute sm:top-1 sm:right-3 sm:z-10 sm:size-7 sm:rounded-lg sm:hover:bg-white/70 dark:text-zinc-400 dark:hover:text-zinc-100 dark:sm:hover:bg-zinc-800"
              title="收合"
              aria-label="收合控制列"
              data-testid="study-room-banner-minimize"
              @click="toggleMinimized(true)"
            >
              <ChevronDownIcon class="size-5" />
            </button>

            <!-- Minimized: one line with what's running, tap to expand -->
            <button
              v-show="minimized"
              type="button"
              class="relative flex w-full items-center gap-3 px-4 pt-3 pb-[calc(var(--safe-bottom)+0.75rem)] text-left sm:px-6"
              aria-label="展開控制列"
              data-testid="study-room-banner-expand"
              @click="toggleMinimized(false)"
            >
              <span v-if="timer.hasTimer()" class="min-w-0 flex-1">
                <span
                  class="block text-xs font-semibold tracking-wide"
                  :class="timer.timerPhaseClass()"
                  >{{ timer.timerPhaseLabel() }}</span
                >
                <span
                  class="block truncate font-semibold text-theme-900 dark:text-zinc-100"
                  >{{ timer.myActivityLabel() }}</span
                >
              </span>
              <span
                v-else
                class="min-w-0 flex-1 truncate font-semibold text-theme-900 dark:text-zinc-100"
                >尚未開始專注</span
              >
              <span
                v-if="timer.hasTimer()"
                class="text-2xl leading-none font-bold text-theme-900 tabular-nums dark:text-zinc-100"
                data-testid="study-room-banner-mini-countdown"
                >{{ timer.myRemainingLabel() }}</span
              >
              <ChevronUpIcon class="size-5 shrink-0 text-theme-700" />
            </button>

            <div
              v-show="!minimized"
              class="relative max-h-[70dvh] overflow-y-auto overscroll-contain px-4 pt-0 pb-[calc(var(--safe-bottom)+1rem)] sm:px-6 sm:pt-6 sm:pb-[calc(var(--safe-bottom)+1.25rem)]"
            >
              <!-- Spoken through the page's announcer; shown here so a
              failed start/pause/leave isn't silent on screen either. -->
              <p
                v-if="timer.errorMessage"
                class="mb-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
                data-testid="study-room-timer-error"
              >
                <ExclamationTriangleIcon class="size-4 shrink-0" />
                <span>{{ timer.errorMessage }}</span>
              </p>

              <!-- Not timing yet: pick activity/subject/mode, then start -->
              <div
                v-show="!timer.hasTimer()"
                class="flex flex-col gap-4 lg:flex-row lg:items-end lg:gap-6"
                data-testid="study-room-timer-form"
              >
                <div
                  class="hidden items-center gap-3 lg:flex lg:w-12 lg:shrink-0 lg:self-center"
                >
                  <DeskLamp
                    :has-timer="timer.hasTimer()"
                    size-class="size-12 shrink-0"
                  />
                </div>

                <div class="flex flex-1 flex-col gap-3">
                  <ActivityPicker v-model="timer.selectedVerb" :verbs="verbs" />

                  <div
                    class="grid gap-3 sm:grid-cols-[minmax(10rem,1.3fr)_auto]"
                  >
                    <label class="block">
                      <span
                        class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
                        >科目</span
                      >
                      <div class="relative">
                        <select
                          v-model="timer.selectedSubjectCourseId"
                          data-testid="study-room-subject-select"
                          class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-base focus:border-orange-300 focus:ring-orange-300 sm:text-sm dark:border-zinc-700 dark:bg-zinc-900"
                        >
                          <option
                            v-for="subject in subjects"
                            :key="subject.id ?? ''"
                            :value="subject.id ?? ''"
                          >
                            {{ subject.name }}
                          </option>
                        </select>
                      </div>
                    </label>

                    <div>
                      <span
                        class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
                        >計時方式</span
                      >
                      <div class="flex flex-wrap items-center gap-2">
                        <div
                          class="grid w-full grid-cols-3 rounded-lg border border-theme-200 bg-white p-0.5 text-center sm:inline-flex sm:w-auto dark:border-zinc-700 dark:bg-zinc-900"
                          role="radiogroup"
                          aria-label="計時方式"
                        >
                          <label
                            class="cursor-pointer rounded-md px-3 py-1.5 text-sm font-medium transition has-checked:bg-theme-700 has-checked:text-white has-focus-visible:ring-2 has-focus-visible:ring-theme-500 has-focus-visible:ring-offset-2 dark:has-checked:bg-theme-500 dark:has-checked:text-zinc-950 dark:has-focus-visible:ring-offset-zinc-900"
                          >
                            <input
                              v-model="timer.timerMode"
                              type="radio"
                              value="pomodoro"
                              class="sr-only"
                              data-testid="study-room-mode-pomodoro"
                            />
                            番茄鐘
                          </label>
                          <label
                            class="cursor-pointer rounded-md px-3 py-1.5 text-sm font-medium transition has-checked:bg-theme-700 has-checked:text-white has-focus-visible:ring-2 has-focus-visible:ring-theme-500 has-focus-visible:ring-offset-2 dark:has-checked:bg-theme-500 dark:has-checked:text-zinc-950 dark:has-focus-visible:ring-offset-zinc-900"
                          >
                            <input
                              v-model="timer.timerMode"
                              type="radio"
                              value="custom"
                              class="sr-only"
                              data-testid="study-room-mode-custom"
                            />
                            倒數
                          </label>
                          <label
                            class="cursor-pointer rounded-md px-3 py-1.5 text-sm font-medium transition has-checked:bg-theme-700 has-checked:text-white has-focus-visible:ring-2 has-focus-visible:ring-theme-500 has-focus-visible:ring-offset-2 dark:has-checked:bg-theme-500 dark:has-checked:text-zinc-950 dark:has-focus-visible:ring-offset-zinc-900"
                          >
                            <input
                              v-model="timer.timerMode"
                              type="radio"
                              value="count_up"
                              class="sr-only"
                              data-testid="study-room-mode-count-up"
                            />
                            正數
                          </label>
                        </div>

                        <button
                          v-show="timer.timerMode === 'pomodoro'"
                          type="button"
                          class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-theme-300 px-2.5 py-1.5 text-xs text-theme-700 transition hover:border-theme-400 hover:bg-white dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                          data-testid="study-room-cycle-settings"
                          @click="timer.openCycleSettings()"
                        >
                          <span>{{ timer.cycleChipLabel() }}</span>
                        </button>

                        <label
                          v-show="timer.timerMode === 'custom'"
                          class="inline-flex items-center gap-1.5 text-sm text-theme-700 dark:text-zinc-300"
                        >
                          <input
                            v-model.number="timer.customMinutes"
                            type="number"
                            :min="clientConfig.timerCustomMinMinutes"
                            :max="clientConfig.timerCustomMaxMinutes"
                            data-testid="study-room-custom-minutes"
                            class="w-20 rounded-lg border border-theme-200 bg-white px-2 py-1.5 text-base tabular-nums sm:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                          />
                          分鐘
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  class="flex items-center gap-2 lg:shrink-0 zoomed:max-sm:flex-wrap"
                >
                  <button
                    type="button"
                    :disabled="timer.panelBusy"
                    data-testid="study-room-start-timer"
                    class="inline-flex flex-[2] items-center justify-center gap-2 rounded-xl bg-theme-700 px-6 py-3 text-base font-semibold whitespace-nowrap text-white shadow-md shadow-theme-700/20 transition hover:bg-theme-800 disabled:opacity-50 lg:flex-none dark:bg-theme-500 dark:text-zinc-950 dark:hover:bg-theme-400 zoomed:max-sm:basis-full"
                    @click="
                      act(
                        timer.startTimer,
                        'study-room-pause-timer',
                        'study-room-stop-timer'
                      )
                    "
                  >
                    <PlaySolidIcon class="size-5" />
                    開始專注
                  </button>

                  <button
                    type="button"
                    :disabled="timer.panelBusy"
                    data-testid="study-room-leave-seat"
                    class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl border border-theme-300 bg-white/70 px-4 py-3 text-sm font-medium whitespace-nowrap text-theme-800 transition hover:bg-white disabled:opacity-50 lg:flex-none dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    @click="timer.leave()"
                  >
                    <ArrowRightStartOnRectangleIcon class="size-4" />
                    離開座位
                  </button>
                </div>
              </div>

              <!-- Timing: countdown, round, start-break / next-round / stop -->
              <div
                v-show="timer.hasTimer()"
                class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-3 lg:flex lg:flex-row lg:gap-6"
                data-testid="study-room-timer-panel"
              >
                <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                  <DeskLamp
                    :has-timer="timer.hasTimer()"
                    size-class="size-10 shrink-0 sm:size-14"
                  />
                  <div class="min-w-0">
                    <p
                      class="text-xs font-semibold tracking-wide"
                      :class="timer.timerPhaseClass()"
                      data-testid="study-room-timer-phase"
                    >
                      {{ timer.timerPhaseLabel() }}
                    </p>
                    <p class="flex min-w-0 items-center gap-1.5">
                      <span
                        class="truncate text-lg font-semibold text-theme-900 sm:text-xl dark:text-zinc-100"
                        data-testid="study-room-timer-activity"
                        >{{ timer.myActivityLabel() }}</span
                      >
                      <button
                        v-show="timer.canChangeActivity()"
                        type="button"
                        class="inline-flex shrink-0 items-center rounded-lg p-1 text-theme-700 transition hover:bg-white hover:text-theme-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        title="變更活動"
                        aria-label="變更活動"
                        data-testid="study-room-change-activity-open"
                        @click="timer.openChangeActivity()"
                      >
                        <PencilSquareIcon class="size-4" />
                      </button>
                    </p>
                    <div
                      class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-theme-700 sm:mt-1.5 dark:text-zinc-400"
                    >
                      <span
                        v-show="timer.isPomodoro()"
                        class="inline-flex items-center gap-1"
                        data-testid="study-room-cycle-dots"
                        aria-hidden="true"
                      >
                        <span
                          v-for="dot in timer.cycleDots()"
                          :key="dot.id"
                          class="size-2 rounded-full transition"
                          :class="timer.cycleDotClass(dot)"
                        ></span>
                      </span>
                      <span data-testid="study-room-round-label">{{
                        timer.roundLabel()
                      }}</span>
                      <span aria-hidden="true">·</span>
                      <span>{{ timer.timerEndsAtLabel() }}</span>
                    </div>
                  </div>
                </div>

                <div
                  class="text-right lg:px-4 lg:text-center zoomed:max-sm:col-span-2 zoomed:max-sm:text-center"
                >
                  <p
                    class="text-4xl leading-none font-bold text-theme-900 tabular-nums sm:text-6xl dark:text-zinc-100"
                    data-testid="study-room-your-countdown"
                  >
                    {{ timer.myRemainingLabel() }}
                  </p>
                </div>

                <div
                  class="col-span-2 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center lg:justify-end"
                >
                  <button
                    type="button"
                    data-testid="study-room-focus-mode-open"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-theme-300 bg-white/70 px-3 py-3 text-sm font-medium text-theme-800 transition hover:bg-white sm:py-2.5 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    title="全螢幕專注"
                    @click="timer.openFocusMode()"
                  >
                    <ArrowsPointingOutIcon class="size-4" />
                    全螢幕
                  </button>

                  <button
                    v-show="timer.canPause()"
                    type="button"
                    :disabled="timer.panelBusy"
                    data-testid="study-room-pause-timer"
                    class="inline-flex items-center justify-center gap-1 rounded-xl border border-theme-300 bg-white/70 px-3 py-3 text-sm font-medium text-theme-800 transition hover:bg-white disabled:opacity-50 sm:py-2.5 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    @click="act(timer.pauseTimer, 'study-room-resume-timer')"
                  >
                    <PauseIcon class="size-4" />
                    暫停
                  </button>

                  <button
                    v-show="timer.isPaused()"
                    type="button"
                    :disabled="timer.panelBusy"
                    data-testid="study-room-resume-timer"
                    class="order-first col-span-2 inline-flex items-center justify-center gap-1.5 rounded-xl bg-theme-700 px-4 py-3 text-sm font-semibold text-white shadow-md shadow-theme-700/20 transition hover:bg-theme-800 disabled:opacity-50 sm:order-none sm:col-span-1 sm:py-2.5 dark:bg-theme-500 dark:text-zinc-950 dark:hover:bg-theme-400"
                    @click="act(timer.resumeTimer, 'study-room-pause-timer')"
                  >
                    <PlaySolidIcon class="size-4" />
                    繼續
                  </button>

                  <button
                    v-show="timer.canStartBreak()"
                    type="button"
                    :disabled="timer.panelBusy"
                    data-testid="study-room-start-break"
                    class="order-first col-span-2 inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-800 disabled:opacity-50 sm:order-none sm:col-span-1 sm:py-2.5"
                    @click="
                      act(
                        timer.startBreak,
                        'study-room-next-round',
                        'study-room-stop-timer'
                      )
                    "
                  >
                    <SparklesIcon class="size-4" />
                    <span>{{ timer.startBreakLabel() }}</span>
                  </button>

                  <button
                    v-show="timer.canStartNextRound()"
                    type="button"
                    :disabled="timer.panelBusy"
                    data-testid="study-room-next-round"
                    class="order-first col-span-2 inline-flex items-center justify-center gap-1.5 rounded-xl bg-theme-700 px-4 py-3 text-sm font-semibold text-white shadow-md shadow-theme-700/20 transition hover:bg-theme-800 disabled:opacity-50 sm:order-none sm:col-span-1 sm:py-2.5 dark:bg-theme-500 dark:text-zinc-950 dark:hover:bg-theme-400"
                    @click="
                      act(
                        timer.startNextRound,
                        'study-room-pause-timer',
                        'study-room-stop-timer'
                      )
                    "
                  >
                    <PlaySolidIcon class="size-4" />
                    <span>{{ timer.nextRoundLabel() }}</span>
                  </button>

                  <button
                    type="button"
                    :disabled="timer.panelBusy"
                    data-testid="study-room-stop-timer"
                    class="inline-flex items-center justify-center gap-1 rounded-xl border border-theme-300 bg-white/70 px-3 py-3 text-sm font-medium text-theme-800 transition hover:bg-white disabled:opacity-50 sm:py-2.5 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    @click="act(timer.stopTimer, 'study-room-start-timer')"
                  >
                    <StopIcon class="size-4" />
                    結束
                  </button>

                  <button
                    type="button"
                    :disabled="timer.panelBusy"
                    data-testid="study-room-leave-seat-running"
                    class="inline-flex items-center justify-center gap-1 rounded-xl px-3 py-3 text-sm font-medium text-theme-700 transition hover:bg-white/70 hover:text-theme-900 disabled:opacity-50 sm:py-2.5 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    @click="timer.leave()"
                  >
                    <ArrowRightStartOnRectangleIcon class="size-4" />
                    離開座位
                  </button>
                </div>
              </div>
            </div>

            <div
              v-show="timer.hasTimer() && timer.hasCountdownEnd()"
              class="absolute inset-x-0 bottom-0 h-1.5 bg-theme-200/80 dark:bg-zinc-800"
              role="progressbar"
              aria-label="計時進度"
              :aria-valuenow="timer.progressPercent()"
              aria-valuemin="0"
              aria-valuemax="100"
              data-testid="study-room-progress"
            >
              <div
                class="h-full transition-[width] duration-1000 ease-linear"
                :class="timer.progressBarClass()"
                :style="timer.progressStyle()"
                data-testid="study-room-progress-bar"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <Modal
      :open="timer.cycleSettingsOpen"
      title="設定你的番茄鐘"
      description="幾分鐘專注、休息多久、幾輪之後長休息一次。設定會記住，下次坐下時沿用。"
      data-testid="study-room-cycle-modal"
      @close="timer.cycleSettingsOpen = false"
    >
      <div class="grid grid-cols-2 gap-3">
        <label class="block">
          <span
            class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
            >專注（分鐘）</span
          >
          <input
            v-model.number="timer.cycle.focusMinutes"
            type="number"
            :min="timer.cycleBound('focus', 0)"
            :max="timer.cycleBound('focus', 1)"
            data-testid="study-room-cycle-focus"
            class="w-full rounded-lg border border-theme-200 bg-white px-3 py-2 text-base tabular-nums sm:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
          />
        </label>
        <label class="block">
          <span
            class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
            >短休息（分鐘）</span
          >
          <input
            v-model.number="timer.cycle.shortBreakMinutes"
            type="number"
            :min="timer.cycleBound('break', 0)"
            :max="timer.cycleBound('break', 1)"
            data-testid="study-room-cycle-short-break"
            class="w-full rounded-lg border border-theme-200 bg-white px-3 py-2 text-base tabular-nums sm:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
          />
        </label>
        <label class="block">
          <span
            class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
            >長休息（分鐘）</span
          >
          <input
            v-model.number="timer.cycle.longBreakMinutes"
            type="number"
            :min="timer.cycleBound('break', 0)"
            :max="timer.cycleBound('break', 1)"
            data-testid="study-room-cycle-long-break"
            class="w-full rounded-lg border border-theme-200 bg-white px-3 py-2 text-base tabular-nums sm:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
          />
        </label>
        <label class="block">
          <span
            class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
            >每幾輪長休息一次</span
          >
          <input
            v-model.number="timer.cycle.roundsPerCycle"
            type="number"
            :min="timer.cycleBound('rounds', 0)"
            :max="timer.cycleBound('rounds', 1)"
            data-testid="study-room-cycle-rounds"
            class="w-full rounded-lg border border-theme-200 bg-white px-3 py-2 text-base tabular-nums sm:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
          />
        </label>
      </div>

      <p
        class="mt-4 rounded-lg bg-theme-100 px-3 py-2 text-sm text-theme-700 dark:bg-zinc-800 dark:text-zinc-300"
      >
        <span data-testid="study-room-cycle-summary">{{
          timer.cycleSummaryLabel()
        }}</span>
      </p>

      <template #footer>
        <div class="flex w-full items-center justify-between gap-2">
          <button
            type="button"
            class="text-sm text-theme-700 underline-offset-2 hover:underline dark:text-zinc-400"
            @click="timer.resetCycle()"
          >
            恢復預設
          </button>
          <button
            type="button"
            data-testid="study-room-cycle-done"
            class="inline-flex items-center rounded-lg bg-theme-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-theme-800 dark:bg-theme-500 dark:text-zinc-950 dark:hover:bg-theme-400"
            @click="timer.closeCycleSettings()"
          >
            完成
          </button>
        </div>
      </template>
    </Modal>

    <Modal
      :open="timer.activityModalOpen"
      title="變更活動"
      data-testid="study-room-change-activity-modal"
      @close="timer.closeChangeActivity()"
    >
      <div class="flex flex-col gap-3">
        <ActivityPicker
          v-model="timer.changeVerb"
          :verbs="verbs"
          group-testid="study-room-change-verb-group"
          testid-prefix="study-room-change-verb-"
          grid-cols-class="grid-cols-3"
        />

        <label class="block">
          <span
            class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
            >科目</span
          >
          <select
            v-model="timer.changeSubjectCourseId"
            data-testid="study-room-change-subject-select"
            class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-base focus:border-orange-300 focus:ring-orange-300 sm:text-sm dark:border-zinc-700 dark:bg-zinc-900"
          >
            <option
              v-for="subject in subjects"
              :key="subject.id ?? ''"
              :value="subject.id ?? ''"
            >
              {{ subject.name }}
            </option>
          </select>
        </label>
      </div>

      <template #footer>
        <div class="flex w-full items-center justify-end gap-2">
          <button
            type="button"
            class="rounded-lg px-4 py-2 text-sm font-medium text-theme-700 transition hover:bg-theme-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
            @click="timer.closeChangeActivity()"
          >
            取消
          </button>
          <button
            type="button"
            :disabled="timer.panelBusy"
            data-testid="study-room-change-activity-save"
            class="inline-flex items-center rounded-lg bg-theme-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-theme-800 disabled:opacity-50 dark:bg-theme-500 dark:text-zinc-950 dark:hover:bg-theme-400"
            @click="timer.changeActivity()"
          >
            儲存
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>
