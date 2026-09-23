<script setup>
// The action banner blown up to fill the whole browser window — the view
// from your carrel. Only shown while a timer is running (timer.hasTimer());
// closes itself if the timer ends or the seat is released underneath you
// (see useStudyTimer's onRoomStateChanged). The cassette player sits on the
// desk.
import { onUnmounted } from 'vue'
import {
  ArrowsPointingInIcon,
  PauseIcon,
  SparklesIcon,
  StopIcon,
} from '@heroicons/vue/24/outline'
import { PlayIcon as PlaySolidIcon } from '@heroicons/vue/24/solid'
import CassettePlayer from './CassettePlayer.vue'
import SceneSkyline from './SceneSkyline.vue'
import SkyLayers from './SkyLayers.vue'

const props = defineProps({
  sky: { type: Object, required: true },
  timer: { type: Object, required: true },
  profile: { type: Object, required: true },
  music: { type: Object, required: true },
})

function handleEscape(event) {
  if (event.key === 'Escape' && props.timer.focusMode) {
    close()
  }
}

function close() {
  props.timer.closeFocusMode(() => props.sky.unmountSkyCanvas('focus'))
}

window.addEventListener('keydown', handleEscape)

onUnmounted(() => {
  window.removeEventListener('keydown', handleEscape)
})
</script>

<template>
  <div
    v-if="timer.focusMode"
    class="fixed inset-0 z-50 animate-focus-in overflow-hidden select-none [--win-h:31%] [--win-top:max(8%,calc(env(safe-area-inset-top)+3.5rem))] sm:[--win-h:37%] sm:[--win-top:max(9%,calc(env(safe-area-inset-top)+3.5rem))] short:[--win-h:24%] short:[--win-top:calc(env(safe-area-inset-top)+3rem)]"
    :style="sky.carrelVars(timer.hasTimer())"
    :data-sky-phase="sky.sky.phase"
    role="dialog"
    aria-modal="true"
    aria-label="專注模式"
    data-testid="study-room-focus-mode"
  >
    <div
      class="absolute inset-0 bg-[linear-gradient(to_bottom,var(--c-wall)_0%,var(--c-wall)_60%,var(--c-wall-deep)_100%)] transition-[background] duration-1000"
      aria-hidden="true"
    ></div>

    <div
      class="absolute top-[calc(var(--win-top)+var(--win-h))] left-1/2 h-[30%] w-[80%] max-w-5xl -translate-x-1/2 opacity-70 blur-2xl transition-[background] duration-1000"
      :style="sky.windowLightStyle()"
      aria-hidden="true"
    ></div>

    <div
      class="pointer-events-none absolute right-[-6%] bottom-[2%] size-[70vmin] rounded-full bg-amber-200 opacity-(--c-lamp) mix-blend-soft-light blur-3xl transition-opacity duration-1000"
      aria-hidden="true"
    ></div>
    <div
      class="pointer-events-none absolute right-[-2%] bottom-[6%] size-[36vmin] rounded-full bg-amber-300/60 opacity-(--c-lamp) blur-2xl transition-opacity duration-1000"
      aria-hidden="true"
    ></div>

    <div
      class="absolute inset-x-0 top-0 z-10 flex items-center justify-between gap-3 pt-[max(0.75rem,env(safe-area-inset-top))] pr-[max(1rem,env(safe-area-inset-right))] pb-3 pl-[max(1rem,env(safe-area-inset-left))] sm:pr-[max(1.5rem,env(safe-area-inset-right))] sm:pl-[max(1.5rem,env(safe-area-inset-left))]"
      :style="sky.focusInkStyle()"
    >
      <div class="flex min-w-0 items-center gap-2 text-sm">
        <span class="text-xl leading-none">{{ profile.emoji }}</span>
        <span class="truncate font-medium">{{ profile.nickname }}</span>
        <span class="hidden opacity-60 sm:inline">·</span>
        <span class="hidden truncate opacity-60 sm:inline">{{
          timer.mySeatLabel()
        }}</span>
      </div>
      <p class="hidden text-sm font-medium tabular-nums opacity-80 sm:block">
        <span data-testid="study-room-focus-clock">{{
          sky.clockTimeLabel()
        }}</span>
        <span class="mx-1 opacity-60">·</span>
        <span>{{ sky.clockDateLabel() }}</span>
      </p>
      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm backdrop-blur transition"
        :class="sky.focusChromeClass()"
        data-testid="study-room-focus-mode-close"
        @click="close()"
      >
        <ArrowsPointingInIcon class="size-4" />
        離開全螢幕
        <kbd
          class="ml-1 hidden rounded border border-current/30 px-1 text-[0.625rem] opacity-70 sm:inline"
          >Esc</kbd
        >
      </button>
    </div>

    <div
      class="absolute top-(--win-top) left-1/2 h-(--win-h) w-[88%] max-w-5xl -translate-x-1/2 rounded-md shadow-[0_12px_40px_rgba(0,0,0,0.25)] transition-[background-color] transition-[background] duration-1000"
      :style="sky.frameStyle()"
      role="img"
      :aria-label="sky.gardenAriaLabel()"
      data-testid="study-room-focus-window"
    >
      <div
        class="absolute inset-[10px] overflow-hidden shadow-[inset_0_0_0_1px_rgba(0,0,0,0.2)] sm:inset-[14px]"
      >
        <SkyLayers :sky="sky" sky-layout="focus" />

        <SceneSkyline
          id-prefix="study-room-focus"
          svg-class="absolute inset-x-0 bottom-[16%] h-[22%] w-full"
        />

        <svg
          class="absolute inset-x-0 bottom-[14%] h-[5%] w-full"
          viewBox="0 0 1000 100"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <path
            d="M0 100 L0 50 C25 10 55 10 80 50 C105 10 135 10 160 50 C185 10 215 10 240 50 C265 10 295 10 320 50 C345 10 375 10 400 50 C425 10 455 10 480 50 C505 10 535 10 560 50 C585 10 615 10 640 50 C665 10 695 10 720 50 C745 10 775 10 800 50 C825 10 855 10 880 50 C905 10 935 10 960 50 C975 25 990 25 1000 50 L1000 100 Z"
            fill="var(--g-hedge)"
          />
        </svg>
        <div
          class="absolute inset-x-0 bottom-0 h-[16%] bg-[linear-gradient(to_bottom,var(--g-lawnTop),var(--g-lawnBottom))]"
          aria-hidden="true"
        ></div>
        <svg
          class="absolute inset-x-0 bottom-0 h-[16%] w-full"
          viewBox="0 0 1000 100"
          preserveAspectRatio="xMidYMid slice"
          aria-hidden="true"
        >
          <path
            d="M470 0 C480 30 430 55 400 100 L560 100 C520 60 520 30 528 0 Z"
            fill="var(--g-path)"
            opacity="0.85"
          />
        </svg>

        <svg
          class="absolute bottom-[8%] left-[3%] h-[36%] w-auto"
          viewBox="0 0 60 100"
          aria-hidden="true"
        >
          <rect
            x="27"
            y="60"
            width="6"
            height="40"
            rx="2"
            fill="var(--g-trunk)"
          />
          <circle cx="30" cy="42" r="26" fill="var(--g-canopyDark)" />
          <circle cx="22" cy="36" r="20" fill="var(--g-canopy)" />
          <circle cx="40" cy="30" r="17" fill="var(--g-canopy)" />
        </svg>
        <svg
          class="absolute right-[4%] bottom-[6%] h-[40%] w-auto"
          viewBox="0 0 60 100"
          aria-hidden="true"
        >
          <rect
            x="27"
            y="58"
            width="6"
            height="42"
            rx="2"
            fill="var(--g-trunk)"
          />
          <circle cx="30" cy="40" r="28" fill="var(--g-canopyDark)" />
          <circle cx="38" cy="34" r="20" fill="var(--g-canopy)" />
          <circle cx="18" cy="30" r="15" fill="var(--g-canopy)" />
        </svg>
        <svg
          class="absolute bottom-[5%] left-[35%] h-[5%] w-auto"
          viewBox="0 0 80 40"
          aria-hidden="true"
        >
          <rect
            x="4"
            y="4"
            width="72"
            height="8"
            rx="2"
            fill="var(--g-trunk)"
          />
          <rect
            x="2"
            y="18"
            width="76"
            height="7"
            rx="2"
            fill="var(--g-trunk)"
          />
          <rect x="8" y="12" width="4" height="28" fill="var(--g-trunk)" />
          <rect x="68" y="12" width="4" height="28" fill="var(--g-trunk)" />
        </svg>

        <svg
          class="absolute bottom-[10%] left-[64%] h-[26%] w-auto overflow-visible"
          viewBox="0 0 40 100"
          aria-hidden="true"
        >
          <circle
            cx="20"
            cy="10"
            r="26"
            fill="rgba(255,214,130,0.35)"
            class="[opacity:var(--g-lamp)]"
          />
          <rect
            x="18"
            y="14"
            width="4"
            height="86"
            rx="1"
            fill="var(--g-trunk)"
          />
          <path d="M10 14 L30 14 L26 4 L14 4 Z" fill="var(--g-buildingNear)" />
          <circle
            cx="20"
            cy="12"
            r="5"
            fill="#ffe08a"
            class="[opacity:var(--g-lamp)]"
          />
        </svg>

        <div
          class="absolute inset-x-0 bottom-0 h-[40%] opacity-(--g-firefly) transition-opacity duration-1000"
          aria-hidden="true"
        >
          <span
            class="absolute bottom-[30%] left-[12%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)]"
          ></span>
          <span
            class="absolute bottom-[55%] left-[31%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)] [animation-delay:-2.3s]"
          ></span>
          <span
            class="absolute bottom-[40%] left-[52%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)] [animation-delay:-4.1s]"
          ></span>
          <span
            class="absolute bottom-[25%] left-[86%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)] [animation-delay:-5.6s]"
          ></span>
        </div>

        <div
          class="pointer-events-none absolute inset-0 bg-[linear-gradient(115deg,rgba(255,255,255,0.16)_0%,rgba(255,255,255,0.04)_38%,transparent_60%)]"
          aria-hidden="true"
        ></div>
      </div>

      <div
        class="pointer-events-none absolute inset-y-0 left-1/2 w-2 -translate-x-1/2 transition-[background-color] duration-1000 sm:w-3"
        :style="sky.frameStyle()"
        aria-hidden="true"
      ></div>
      <div
        class="pointer-events-none absolute inset-x-0 top-1/2 h-2 -translate-y-1/2 transition-[background-color] duration-1000 sm:h-3"
        :style="sky.frameStyle()"
        aria-hidden="true"
      ></div>
    </div>

    <div
      class="absolute top-[calc(var(--win-top)+var(--win-h))] left-1/2 h-2.5 w-[92%] max-w-[calc(64rem+2rem)] -translate-x-1/2 rounded-sm shadow-[0_4px_10px_rgba(0,0,0,0.25)] transition-[background-color] transition-[background] duration-1000 sm:h-3.5"
      :style="sky.frameStyle()"
      aria-hidden="true"
    ></div>

    <div
      class="absolute inset-x-0 top-[calc(var(--win-top)+var(--win-h)+4%)] bottom-[18%] flex flex-col items-center justify-center gap-1.5 px-6 text-center sm:gap-2 short:gap-1"
      :style="sky.focusInkStyle()"
    >
      <p
        class="max-w-2xl truncate text-base font-semibold sm:text-xl"
        data-testid="study-room-focus-activity"
      >
        {{ timer.myActivityLabel() }}
      </p>

      <p
        class="font-mono text-[clamp(3.25rem,min(11vw,16vh),7rem)] leading-none font-bold tracking-tight tabular-nums short:text-[clamp(2.5rem,13vh,4rem)]"
        data-testid="study-room-focus-countdown"
      >
        {{ timer.myRemainingLabel() }}
      </p>

      <div
        v-show="timer.hasCountdownEnd()"
        class="h-1.5 w-56 overflow-hidden rounded-full sm:w-80"
        :class="sky.focusProgressTrackClass()"
        role="progressbar"
        aria-label="計時進度"
        :aria-valuenow="timer.progressPercent()"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        <div
          class="h-full rounded-full bg-current transition-[width] duration-1000 ease-linear"
          :style="timer.progressStyle()"
          data-testid="study-room-focus-progress-bar"
        ></div>
      </div>

      <div
        class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm sm:text-base"
      >
        <span class="font-semibold" data-testid="study-room-focus-phase">{{
          timer.timerPhaseLabel()
        }}</span>
        <span
          v-show="timer.isPomodoro()"
          class="inline-flex items-center gap-1.5"
          aria-hidden="true"
        >
          <span
            v-for="dot in timer.cycleDots()"
            :key="dot.id"
            class="size-2.5 rounded-full transition"
            :class="sky.focusDotClass(dot)"
          ></span>
        </span>
        <span class="opacity-80">{{ timer.roundLabel() }}</span>
        <span class="opacity-60" aria-hidden="true">·</span>
        <span class="opacity-80">{{ timer.timerEndsAtLabel() }}</span>
      </div>

      <div
        class="mt-1 flex flex-wrap items-center justify-center gap-2 sm:mt-2 short:mt-0"
      >
        <button
          v-show="timer.canPause()"
          type="button"
          :disabled="timer.panelBusy"
          class="inline-flex items-center gap-1.5 rounded-full border px-5 py-3 text-sm font-medium backdrop-blur transition disabled:opacity-50 sm:py-2.5 short:py-2"
          :class="sky.focusChromeClass()"
          data-testid="study-room-focus-pause-timer"
          @click="timer.pauseTimer()"
        >
          <PauseIcon class="size-4" />
          暫停
        </button>
        <button
          v-show="timer.isPaused()"
          type="button"
          :disabled="timer.panelBusy"
          class="inline-flex items-center gap-1.5 rounded-full border px-5 py-3 text-sm font-semibold backdrop-blur transition disabled:opacity-50 sm:py-2.5 short:py-2"
          :class="sky.focusChromeClass()"
          data-testid="study-room-focus-resume-timer"
          @click="timer.resumeTimer()"
        >
          <PlaySolidIcon class="size-4" />
          繼續
        </button>
        <button
          v-show="timer.canStartBreak()"
          type="button"
          :disabled="timer.panelBusy"
          class="inline-flex items-center gap-1.5 rounded-full border px-5 py-3 text-sm font-semibold backdrop-blur transition disabled:opacity-50 sm:py-2.5 short:py-2"
          :class="sky.focusChromeClass()"
          data-testid="study-room-focus-start-break"
          @click="timer.startBreak()"
        >
          <SparklesIcon class="size-4" />
          <span>{{ timer.startBreakLabel() }}</span>
        </button>
        <button
          v-show="timer.canStartNextRound()"
          type="button"
          :disabled="timer.panelBusy"
          class="inline-flex items-center gap-1.5 rounded-full border px-5 py-3 text-sm font-semibold backdrop-blur transition disabled:opacity-50 sm:py-2.5 short:py-2"
          :class="sky.focusChromeClass()"
          data-testid="study-room-focus-next-round"
          @click="timer.startNextRound()"
        >
          <PlaySolidIcon class="size-4" />
          <span>{{ timer.nextRoundLabel() }}</span>
        </button>
        <button
          type="button"
          :disabled="timer.panelBusy"
          class="inline-flex items-center gap-1.5 rounded-full border px-5 py-3 text-sm font-medium backdrop-blur transition disabled:opacity-50 sm:py-2.5 short:py-2"
          :class="sky.focusChromeClass()"
          data-testid="study-room-focus-stop-timer"
          @click="timer.stopTimer()"
        >
          <StopIcon class="size-4" />
          結束計時
        </button>
      </div>
    </div>

    <div
      class="pointer-events-none absolute inset-x-0 bottom-0 h-[17%] bg-[linear-gradient(to_bottom,var(--c-desk-top),var(--c-desk-bottom))] shadow-[0_-8px_30px_rgba(0,0,0,0.3)] transition-[background] duration-1000"
      aria-hidden="true"
    >
      <div class="absolute inset-x-0 top-0 h-1 bg-white/15"></div>
      <div
        class="absolute inset-0 bg-[repeating-linear-gradient(90deg,transparent_0_9rem,rgba(0,0,0,0.06)_9rem_calc(9rem+2px))]"
      ></div>
      <div
        class="absolute top-[26%] left-[10%] h-[22%] w-28 rotate-[-4deg] rounded-sm bg-theme-800/85 shadow-md sm:w-40"
      >
        <div
          class="absolute inset-y-0 left-0 w-2 rounded-l-sm bg-theme-900"
        ></div>
        <div class="absolute top-1/2 right-4 left-6 h-px bg-white/20"></div>
      </div>
      <div
        class="absolute top-[18%] left-[12%] h-[22%] w-24 rotate-[2deg] rounded-sm bg-sky-900/80 shadow-md sm:w-36"
      >
        <div
          class="absolute inset-y-0 left-0 w-2 rounded-l-sm bg-sky-950"
        ></div>
      </div>
      <div
        class="absolute top-[20%] left-[34%] h-[38%] w-10 rounded-t-sm rounded-b-lg bg-theme-50/90 shadow-md sm:w-12"
      >
        <div
          class="absolute top-[18%] -right-3 h-[45%] w-4 rounded-r-full border-4 border-l-0 border-theme-50/90"
        ></div>
        <div
          class="absolute inset-x-1.5 top-1 h-1.5 rounded-full bg-theme-700/70"
        ></div>
      </div>
    </div>

    <!--
      The same deck as on the Wall, standing on the desk. It shares the page's
      music state, so a tape keeps playing straight through entering and
      leaving focus mode.
    -->
    <div
      v-if="music.available"
      class="absolute bottom-[max(0.75rem,env(safe-area-inset-bottom))] left-1/2 z-10 w-64 max-w-[calc(100%-2rem)] -translate-x-1/2"
    >
      <CassettePlayer :music="music" />
    </div>

    <svg
      class="pointer-events-none absolute right-[4%] bottom-[12%] h-[9%] w-auto overflow-visible sm:right-[6%] sm:h-[22%] short:h-[24%]"
      viewBox="0 0 48 48"
      aria-hidden="true"
    >
      <defs>
        <radialGradient id="study-room-focus-lamp-glow">
          <stop offset="0%" stop-color="#ffe6b0" stop-opacity="0.85" />
          <stop offset="40%" stop-color="#ffd682" stop-opacity="0.45" />
          <stop offset="100%" stop-color="#ffd682" stop-opacity="0" />
        </radialGradient>
      </defs>

      <circle
        cx="12.13"
        cy="22.44"
        r="17"
        fill="url(#study-room-focus-lamp-glow)"
        class="[opacity:var(--c-lamp)] transition-opacity duration-1000"
      />

      <g fill="var(--c-desk-bottom)">
        <path d="M29 41.8 Q35 35.5 41 41.8 Z" />
        <rect x="26" y="41.8" width="18" height="2.8" rx="1.4" />
        <path
          d="M35 39 L32.5 19 L19 12"
          fill="none"
          stroke="var(--c-desk-bottom)"
          stroke-width="3"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <circle cx="32.5" cy="19" r="2.4" />
      </g>

      <path
        d="M16.33 10.24 L21.67 13.76 Q21.83 21.35 19.31 27.17 Q8.83 27.45 4.95 17.71 Q9.3 13.1 16.33 10.24 Z"
        fill="var(--c-frame)"
      />

      <g transform="rotate(33.4 12.13 22.44)">
        <ellipse
          data-testid="study-room-lamp-shade-mouth"
          cx="12.13"
          cy="22.44"
          rx="8.6"
          ry="3"
          fill="var(--c-desk-bottom)"
        />
        <ellipse
          cx="12.13"
          cy="22.44"
          rx="8.6"
          ry="3"
          fill="#ffd06a"
          class="[opacity:var(--c-lamp)] transition-opacity duration-1000"
        />
      </g>

      <circle
        data-testid="study-room-lamp-bulb"
        cx="12.13"
        cy="22.44"
        r="2.1"
        fill="#fff4d6"
        class="[opacity:calc(0.35+0.65*var(--c-lamp))] transition-opacity duration-1000"
      />

      <circle cx="19" cy="12" r="2.2" fill="var(--c-desk-bottom)" />
    </svg>
  </div>
</template>
