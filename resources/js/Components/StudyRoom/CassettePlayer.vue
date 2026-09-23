<script setup>
// The cassette deck on the Wall (state and playback live in
// useStudyRoomMusic): a slim strip with a mini cassette, the track, play and
// next. Tapping the track opens a popover above it with the playlists,
// previous/next, eject, volume and the track's credits. A cassette is only in
// the deck while a tape is loaded.
import { computed, onMounted, onUnmounted, ref, useId } from 'vue'
import {
  BackwardIcon,
  ForwardIcon,
  PauseIcon,
  PlayIcon,
} from '@heroicons/vue/24/solid'
import MarqueeText from './MarqueeText.vue'

const props = defineProps({
  music: { type: Object, required: true },
})

const root = ref(null)
const open = ref(false)
// The Wall and focus mode each mount a deck, so the popover id can't be fixed.
const popoverId = useId()

// How much tape has wound onto the right reel; the left one has the rest.
const leftTapeRadius = computed(() => 4.5 + 5 * (1 - props.music.progress))
const rightTapeRadius = computed(() => 4.5 + 5 * props.music.progress)

const reelStyle = computed(() => ({
  animationPlayState: props.music.playing ? 'running' : 'paused',
}))

function formatTime(seconds) {
  const whole = Math.max(0, Math.floor(seconds))

  return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}`
}

function selectPlaylist(index) {
  open.value = false
  props.music.selectPlaylist(index)
}

function closeOnOutsidePointer(event) {
  if (open.value && root.value && !root.value.contains(event.target)) {
    open.value = false
  }
}

// Escape closes an open popover first and stops there, so inside focus mode it
// doesn't also leave the fullscreen (which listens on window).
function closeOnEscape(event) {
  if (event.key === 'Escape' && open.value) {
    open.value = false
    event.stopPropagation()
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', closeOnOutsidePointer)
  document.addEventListener('keydown', closeOnEscape)
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', closeOnOutsidePointer)
  document.removeEventListener('keydown', closeOnEscape)
})

const buttonClass =
  'flex size-7 shrink-0 items-center justify-center rounded-lg border-2 border-b-4 border-theme-300 bg-white text-theme-700 transition hover:bg-theme-50 focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:outline-none active:translate-y-px active:border-b-2 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600'
const linkClass =
  'underline decoration-dotted underline-offset-2 hover:decoration-solid'
</script>

<template>
  <section
    ref="root"
    class="relative min-w-0"
    aria-label="背景音樂"
    data-testid="study-room-music-player"
  >
    <div
      v-if="open"
      :id="popoverId"
      class="absolute inset-x-0 bottom-full z-20 mb-2 flex flex-col gap-2 rounded-xl border-2 border-b-4 border-theme-300 bg-theme-100 p-2 shadow-md dark:border-zinc-600 dark:bg-zinc-800"
      data-testid="study-room-music-popover"
    >
      <ul class="flex max-h-28 flex-col gap-1 overflow-y-auto">
        <li v-for="(item, index) in music.playlists" :key="item.id">
          <button
            type="button"
            class="flex w-full items-center gap-2 rounded-md border px-1.5 py-1 text-left transition hover:bg-white focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:outline-none dark:hover:bg-zinc-700"
            :class="
              index === music.playlistIndex
                ? 'border-theme-500 bg-white dark:border-theme-400 dark:bg-zinc-700'
                : 'border-transparent'
            "
            :aria-current="index === music.playlistIndex ? 'true' : undefined"
            :data-testid="'study-room-music-playlist-' + item.id"
            @click="selectPlaylist(index)"
          >
            <img
              v-if="item.coverImageUrl"
              :src="item.coverImageUrl"
              alt=""
              class="size-6 shrink-0 rounded object-cover"
            />
            <span
              v-else
              class="w-6 shrink-0 text-center text-base"
              aria-hidden="true"
              >📼</span
            >
            <span class="min-w-0 flex-1">
              <span
                class="block truncate text-xs font-semibold text-theme-900 dark:text-zinc-100"
                >{{ item.title }}</span
              >
              <span
                class="block text-[0.625rem] text-theme-700 dark:text-zinc-400"
                >{{ item.trackCount }} 首 ·
                {{ formatTime(item.totalDurationSeconds) }}</span
              >
            </span>
          </button>
        </li>
      </ul>

      <p
        v-if="music.loaded && music.track"
        class="truncate text-[0.625rem] text-theme-700 dark:text-zinc-400"
        data-testid="study-room-music-credit"
      >
        <a
          v-if="music.track.sourceUrl"
          :href="music.track.sourceUrl"
          target="_blank"
          rel="noopener noreferrer"
          :class="linkClass"
          >{{ music.track.author }}</a
        >
        <template v-else>{{ music.track.author }}</template>
        ·
        <a
          v-if="music.track.licenseUrl"
          :href="music.track.licenseUrl"
          target="_blank"
          rel="noopener noreferrer"
          :class="linkClass"
          >{{ music.track.license }}</a
        >
        <template v-else>{{ music.track.license }}</template>
      </p>

      <div class="flex items-center gap-1">
        <button
          type="button"
          :class="buttonClass"
          aria-label="上一首"
          data-testid="study-room-music-previous"
          :disabled="!music.loaded"
          @click="music.previous()"
        >
          <BackwardIcon class="size-3.5" />
        </button>
        <button
          type="button"
          :class="buttonClass"
          aria-label="下一首"
          data-testid="study-room-music-popover-next"
          :disabled="!music.loaded"
          @click="music.next()"
        >
          <ForwardIcon class="size-3.5" />
        </button>
        <button
          type="button"
          :class="buttonClass"
          aria-label="取出卡帶"
          data-testid="study-room-music-eject"
          :disabled="!music.loaded"
          @click="music.eject()"
        >
          <svg class="size-3.5" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M8 2.5 13.5 9.5H2.5Z" class="fill-current" />
            <rect
              x="2.5"
              y="11"
              width="11"
              height="2.2"
              rx="1"
              class="fill-current"
            />
          </svg>
        </button>
        <input
          type="range"
          min="0"
          max="1"
          step="0.05"
          :value="music.volume"
          class="ml-1 h-1.5 min-w-0 flex-1 cursor-pointer accent-theme-500"
          aria-label="音量"
          data-testid="study-room-music-volume"
          @input="music.setVolume($event.target.value)"
        />
      </div>
    </div>

    <div
      class="relative flex items-center gap-1.5 rounded-xl border-2 border-b-4 border-theme-300 bg-theme-200 p-1.5 pb-2 shadow-sm dark:border-zinc-600 dark:bg-zinc-800"
    >
      <div
        class="relative h-8 w-12 shrink-0 overflow-hidden rounded-md border-2 border-theme-300 bg-theme-100 dark:border-zinc-600 dark:bg-zinc-900"
      >
        <div
          v-if="!music.loaded"
          class="absolute inset-x-1.5 top-1/2 h-1 -translate-y-1/2 rounded-full bg-theme-300 dark:bg-zinc-700"
          data-testid="study-room-music-empty"
          aria-hidden="true"
        ></div>

        <Transition
          enter-active-class="transition duration-500 ease-out motion-reduce:transition-none"
          enter-from-class="-translate-y-full opacity-0"
          leave-active-class="transition duration-300 ease-in motion-reduce:transition-none"
          leave-to-class="translate-y-full opacity-0"
        >
          <div
            v-if="music.loaded"
            class="absolute inset-0 flex items-center justify-center"
            data-testid="study-room-music-cassette"
          >
            <svg class="h-full" viewBox="0 0 120 76" aria-hidden="true">
              <rect
                x="2"
                y="2"
                width="116"
                height="72"
                rx="7"
                stroke-width="4"
                class="fill-theme-300 stroke-theme-600 dark:fill-zinc-600 dark:stroke-zinc-400"
              />
              <rect
                x="12"
                y="8"
                width="96"
                height="34"
                rx="3"
                class="fill-white dark:fill-zinc-200"
              />
              <rect x="12" y="14" width="96" height="6" class="fill-rose-400" />
              <rect
                x="12"
                y="23"
                width="96"
                height="3"
                class="fill-amber-400"
              />
              <rect
                x="24"
                y="44"
                width="72"
                height="26"
                rx="13"
                stroke-width="3"
                class="fill-theme-100 stroke-theme-500 dark:fill-zinc-800 dark:stroke-zinc-500"
              />

              <g transform="translate(44 57)">
                <circle
                  :r="leftTapeRadius * 1.2"
                  class="fill-theme-800 dark:fill-zinc-950"
                />
                <g
                  class="animate-reel motion-reduce:animate-none"
                  :style="reelStyle"
                >
                  <circle r="5" class="fill-white dark:fill-zinc-300" />
                  <path
                    d="M0 -5 V-2 M4.3 2.5 L1.7 1 M-4.3 2.5 L-1.7 1"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    class="stroke-theme-500 dark:stroke-zinc-600"
                  />
                </g>
              </g>
              <g transform="translate(76 57)">
                <circle
                  :r="rightTapeRadius * 1.2"
                  class="fill-theme-800 dark:fill-zinc-950"
                />
                <g
                  class="animate-reel motion-reduce:animate-none"
                  :style="reelStyle"
                >
                  <circle r="5" class="fill-white dark:fill-zinc-300" />
                  <path
                    d="M0 -5 V-2 M4.3 2.5 L1.7 1 M-4.3 2.5 L-1.7 1"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    class="stroke-theme-500 dark:stroke-zinc-600"
                  />
                </g>
              </g>
            </svg>
          </div>
        </Transition>
      </div>

      <button
        type="button"
        class="min-w-0 flex-1 rounded-md px-1 py-0.5 text-left focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:outline-none"
        :aria-expanded="open ? 'true' : 'false'"
        :aria-controls="popoverId"
        aria-label="選擇卡帶與更多控制"
        data-testid="study-room-music-toggle"
        @click="open = !open"
      >
        <span
          v-if="music.errored"
          class="block truncate text-xs font-semibold text-rose-600 dark:text-rose-400"
          aria-live="polite"
          >這卷帶子讀不出來</span
        >
        <template v-else-if="music.loaded && music.track">
          <MarqueeText
            :text="music.track.title"
            class="text-xs font-semibold text-theme-900 dark:text-zinc-100"
            aria-live="polite"
            data-testid="study-room-music-title"
          />
          <span
            class="block truncate text-[0.625rem] text-theme-800 dark:text-zinc-400"
            >{{ music.track.author }} ·
            <span
              class="font-mono tabular-nums"
              data-testid="study-room-music-time"
              >{{ formatTime(music.currentTime) }}</span
            ></span
          >
        </template>
        <template v-else>
          <span
            class="block truncate text-xs font-semibold text-theme-900 dark:text-zinc-100"
            >背景音樂</span
          >
          <span
            class="block truncate text-[0.625rem] text-theme-800 dark:text-zinc-400"
            >尚未放入卡帶</span
          >
        </template>
      </button>

      <button
        type="button"
        class="flex size-8 shrink-0 items-center justify-center rounded-lg border-2 border-b-4 border-theme-700 bg-theme-500 text-white transition hover:bg-theme-600 focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:ring-offset-1 focus-visible:outline-none active:translate-y-px active:border-b-2 dark:border-theme-800 dark:bg-theme-600 dark:hover:bg-theme-500"
        :aria-label="music.playing ? '暫停' : '播放'"
        :aria-pressed="music.playing ? 'true' : 'false'"
        data-testid="study-room-music-play"
        @click="music.toggle()"
      >
        <PauseIcon v-if="music.playing" class="size-4" />
        <PlayIcon v-else class="size-4" />
      </button>
      <button
        type="button"
        :class="buttonClass + ' max-sm:hidden'"
        aria-label="下一首"
        data-testid="study-room-music-next"
        :disabled="!music.loaded"
        @click="music.next()"
      >
        <ForwardIcon class="size-3.5" />
      </button>

      <span
        class="pointer-events-none absolute inset-x-2 bottom-0.5 h-0.5 overflow-hidden rounded-full bg-theme-100 dark:bg-zinc-900"
        aria-hidden="true"
      >
        <span
          class="block h-full rounded-full bg-theme-500 dark:bg-theme-400"
          :style="{ width: music.progress * 100 + '%' }"
        ></span>
      </span>
    </div>
  </section>
</template>
