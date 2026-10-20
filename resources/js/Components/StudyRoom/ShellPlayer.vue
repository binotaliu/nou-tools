<script setup>
// The study room's music and timer, carried into the rest of the app: a small
// pill above the page bottom with the timer's readout, play/pause and next for
// the tape, and a way back to the room. Anything more (pausing the timer,
// playlists, volume) stays on the study room page, where the tape and the
// seat were started. State lives in module-level singletons
// (useStudyRoomMusic, useStudyRoomBackground), so it survives AppLayout
// remounting on every Inertia visit.
import { computed, onUnmounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  ForwardIcon,
  PauseIcon,
  PlayIcon,
  XMarkIcon,
} from '@heroicons/vue/24/solid'
import useStudyRoomBackground from '../../Composables/useStudyRoomBackground'
import useStudyRoomMusic from '../../Composables/useStudyRoomMusic'
import { remainingLabel } from '../../study-room-clock'

const props = defineProps({
  currentPath: { type: String, required: true },
})

const music = useStudyRoomMusic()
const background = useStudyRoomBackground()

const seat = computed(() => background.state.seat)
const hasTimer = computed(() => Boolean(seat.value?.timerMode))
const onRoomPage = computed(
  () =>
    props.currentPath === '/study-room' ||
    props.currentPath.startsWith('/study-room/')
)
// The room has its own deck and timer, so the pill only shows elsewhere.
const visible = computed(
  () => !onRoomPage.value && (hasTimer.value || music.loaded)
)

const now = ref(Date.now())
let tickHandle = null

function tick() {
  now.value = Date.now()
  background.checkTimerEnd(now.value)
}

watch(
  hasTimer,
  active => {
    clearInterval(tickHandle)
    tickHandle = active ? setInterval(tick, 1000) : null

    if (active) {
      tick()
    }
  },
  { immediate: true }
)

onUnmounted(() => clearInterval(tickHandle))

const phaseLabel = computed(() => {
  const current = seat.value

  if (!current) {
    return ''
  }

  if (current.timerPhase === 'break') {
    return '休息'
  }

  if (current.pausedAt) {
    return '已暫停'
  }

  if (current.timerEndsAt && Date.parse(current.timerEndsAt) <= now.value) {
    return '時間到'
  }

  return '專注'
})

const timerText = computed(() =>
  seat.value ? remainingLabel(seat.value, now.value) : ''
)

const buttonClass =
  'flex size-9 pointer-coarse:size-11 shrink-0 items-center justify-center rounded-full text-theme-700 transition hover:bg-theme-100 focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:outline-none dark:text-zinc-200 dark:hover:bg-zinc-700'
</script>

<template>
  <div
    v-if="visible"
    class="pointer-events-none fixed right-0 bottom-(--pwa-nav-height) left-(--pwa-sidebar-width) z-40 flex justify-center px-3 pb-3 print:hidden"
  >
    <section
      class="pointer-events-auto flex max-w-full items-center gap-1 rounded-full border border-theme-200 bg-white/95 py-1 pr-2 pl-4 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95"
      aria-label="自習室背景播放"
      data-testid="shell-player"
    >
      <div
        v-if="hasTimer"
        class="flex shrink-0 items-baseline gap-2 pr-2 text-theme-900 dark:text-zinc-100"
        role="timer"
        :aria-label="`${phaseLabel}計時`"
        data-testid="shell-player-timer"
      >
        <span class="text-xs text-theme-700 dark:text-zinc-400">{{
          phaseLabel
        }}</span>
        <span class="text-sm font-semibold tabular-nums">{{ timerText }}</span>
      </div>

      <template v-if="music.loaded">
        <button
          type="button"
          :class="buttonClass"
          :aria-label="music.playing ? '暫停背景音樂' : '播放背景音樂'"
          data-testid="shell-player-toggle"
          @click="music.toggle()"
        >
          <PauseIcon v-if="music.playing" class="size-5" />
          <PlayIcon v-else class="size-5" />
        </button>
        <span
          class="max-w-32 min-w-0 truncate text-sm text-theme-800 sm:max-w-56 dark:text-zinc-200"
          data-testid="shell-player-title"
          >{{ music.track?.title }}</span
        >
        <button
          type="button"
          :class="buttonClass"
          aria-label="下一首"
          data-testid="shell-player-next"
          @click="music.next()"
        >
          <ForwardIcon class="size-5" />
        </button>
      </template>

      <Link
        href="/study-room"
        class="shrink-0 rounded-full px-3 py-2 text-sm font-medium text-theme-700 underline decoration-dotted underline-offset-2 hover:decoration-solid focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:outline-none dark:text-zinc-200"
        data-testid="shell-player-room"
      >
        回到自習室
      </Link>

      <button
        v-if="music.loaded"
        type="button"
        :class="buttonClass"
        aria-label="停止並收起背景音樂"
        data-testid="shell-player-eject"
        @click="music.eject()"
      >
        <XMarkIcon class="size-5" />
      </button>
    </section>
  </div>
</template>
