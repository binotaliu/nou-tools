<script setup>
// The cassette deck on the Wall (state and playback live in
// useStudyRoomMusic). A tape is only visible in the bay while one is loaded;
// ejecting empties the bay and swaps it for the shelf of playlists.
import { computed } from 'vue'
import {
  BackwardIcon,
  ForwardIcon,
  PauseIcon,
  PlayIcon,
} from '@heroicons/vue/24/solid'

const props = defineProps({
  music: { type: Object, required: true },
})

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

const buttonClass =
  'flex size-8 items-center justify-center rounded-lg border-2 border-b-4 border-theme-300 bg-white text-theme-700 transition hover:bg-theme-50 focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:outline-none active:translate-y-px active:border-b-2 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600'
</script>

<template>
  <section
    class="relative flex min-w-0 flex-col gap-2 rounded-xl border-2 border-b-4 border-theme-300 bg-theme-200 p-2.5 shadow-sm dark:border-zinc-600 dark:bg-zinc-800"
    aria-label="背景音樂"
    data-testid="study-room-music-player"
  >
    <header class="flex items-center gap-1.5">
      <span
        class="size-1.5 shrink-0 rounded-full transition-[background,box-shadow] duration-500"
        :class="
          music.playing
            ? 'bg-amber-400 shadow-[0_0_6px_2px_rgba(251,191,36,0.55)]'
            : 'bg-theme-300 dark:bg-zinc-600'
        "
        aria-hidden="true"
      ></span>
      <span
        class="text-[10px] font-semibold tracking-wide text-theme-600 dark:text-zinc-400"
        >背景音樂</span
      >
    </header>

    <div
      class="relative h-24 overflow-hidden rounded-lg border-2 border-theme-300 bg-theme-100 dark:border-zinc-600 dark:bg-zinc-900"
    >
      <div
        v-if="music.shelfOpen"
        class="absolute inset-0 flex flex-col gap-1 overflow-y-auto p-1.5"
        data-testid="study-room-music-shelf"
      >
        <button
          v-for="(item, index) in music.playlists"
          :key="item.id"
          type="button"
          class="flex shrink-0 items-center gap-2 rounded-md border px-1.5 py-1 text-left transition hover:bg-white focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:outline-none dark:hover:bg-zinc-800"
          :class="
            index === music.playlistIndex
              ? 'border-theme-500 bg-white dark:border-theme-400 dark:bg-zinc-800'
              : 'border-transparent'
          "
          :aria-current="index === music.playlistIndex ? 'true' : undefined"
          :data-testid="'study-room-music-playlist-' + item.id"
          @click="music.selectPlaylist(index)"
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
            <span class="block text-[10px] text-theme-500 dark:text-zinc-400"
              >{{ item.trackCount }} 首 ·
              {{ formatTime(item.totalDurationSeconds) }}</span
            >
          </span>
        </button>
      </div>

      <template v-else>
        <div
          v-if="!music.loaded"
          class="absolute inset-0 flex flex-col items-center justify-center gap-2"
          data-testid="study-room-music-empty"
        >
          <span
            class="h-1.5 w-3/4 rounded-full bg-theme-300 dark:bg-zinc-700"
            aria-hidden="true"
          ></span>
          <span class="text-[10px] text-theme-500 dark:text-zinc-400"
            >尚未放入卡帶</span
          >
        </div>

        <Transition
          enter-active-class="transition duration-500 ease-out motion-reduce:transition-none"
          enter-from-class="-translate-y-full opacity-0"
          leave-active-class="transition duration-300 ease-in motion-reduce:transition-none"
          leave-to-class="translate-y-full opacity-0"
        >
          <div
            v-if="music.loaded"
            class="absolute inset-0 flex items-center justify-center p-1"
            data-testid="study-room-music-cassette"
          >
            <svg class="h-full" viewBox="0 0 120 76" aria-hidden="true">
              <rect
                x="2"
                y="2"
                width="116"
                height="72"
                rx="7"
                stroke-width="2"
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
              <rect x="12" y="13" width="96" height="5" class="fill-rose-400" />
              <rect
                x="12"
                y="20"
                width="96"
                height="2.5"
                class="fill-amber-400"
              />
              <text
                x="17"
                y="37"
                font-size="9"
                font-weight="700"
                class="fill-theme-700 dark:fill-zinc-700"
              >
                A
              </text>
              <path
                d="M30 34 H88 M30 38 H70"
                stroke-width="1"
                stroke-linecap="round"
                class="stroke-theme-300 dark:stroke-zinc-400"
              />

              <rect
                x="28"
                y="46"
                width="64"
                height="22"
                rx="11"
                stroke-width="1.5"
                class="fill-theme-100 stroke-theme-500 dark:fill-zinc-800 dark:stroke-zinc-500"
              />

              <g transform="translate(44 57)">
                <circle
                  :r="leftTapeRadius"
                  class="fill-theme-800 dark:fill-zinc-950"
                />
                <g
                  class="animate-reel motion-reduce:animate-none"
                  :style="reelStyle"
                >
                  <circle r="4" class="fill-white dark:fill-zinc-300" />
                  <path
                    d="M0 -4 V-1.5 M3.5 2 L1.3 0.8 M-3.5 2 L-1.3 0.8"
                    stroke-width="1.2"
                    stroke-linecap="round"
                    class="stroke-theme-500 dark:stroke-zinc-600"
                  />
                </g>
              </g>
              <g transform="translate(76 57)">
                <circle
                  :r="rightTapeRadius"
                  class="fill-theme-800 dark:fill-zinc-950"
                />
                <g
                  class="animate-reel motion-reduce:animate-none"
                  :style="reelStyle"
                >
                  <circle r="4" class="fill-white dark:fill-zinc-300" />
                  <path
                    d="M0 -4 V-1.5 M3.5 2 L1.3 0.8 M-3.5 2 L-1.3 0.8"
                    stroke-width="1.2"
                    stroke-linecap="round"
                    class="stroke-theme-500 dark:stroke-zinc-600"
                  />
                </g>
              </g>

              <circle
                cx="8"
                cy="8"
                r="1.6"
                class="fill-theme-500 dark:fill-zinc-400"
              />
              <circle
                cx="112"
                cy="8"
                r="1.6"
                class="fill-theme-500 dark:fill-zinc-400"
              />
              <circle
                cx="8"
                cy="68"
                r="1.6"
                class="fill-theme-500 dark:fill-zinc-400"
              />
              <circle
                cx="112"
                cy="68"
                r="1.6"
                class="fill-theme-500 dark:fill-zinc-400"
              />
            </svg>
          </div>
        </Transition>
      </template>
    </div>

    <div class="min-h-[2.25rem] min-w-0" aria-live="polite">
      <p
        v-if="music.errored"
        class="text-xs font-semibold text-rose-600 dark:text-rose-400"
      >
        這卷帶子讀不出來
      </p>
      <template v-else-if="music.loaded && music.track">
        <p
          class="truncate text-xs font-semibold text-theme-900 dark:text-zinc-100"
          data-testid="study-room-music-title"
        >
          {{ music.track.title }}
        </p>
        <p
          class="truncate text-[10px] text-theme-600 dark:text-zinc-400"
          data-testid="study-room-music-credit"
        >
          <a
            v-if="music.track.sourceUrl"
            :href="music.track.sourceUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="underline decoration-dotted underline-offset-2 hover:decoration-solid"
            >{{ music.track.author }}</a
          >
          <template v-else>{{ music.track.author }}</template>
          ·
          <a
            v-if="music.track.licenseUrl"
            :href="music.track.licenseUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="underline decoration-dotted underline-offset-2 hover:decoration-solid"
            >{{ music.track.license }}</a
          >
          <template v-else>{{ music.track.license }}</template>
        </p>
      </template>
      <p v-else class="text-xs text-theme-600 dark:text-zinc-400">
        按下播放，聽點音樂
      </p>
    </div>

    <div
      class="flex items-center gap-1.5 font-mono text-[10px] text-theme-600 tabular-nums dark:text-zinc-400"
    >
      <span data-testid="study-room-music-time">{{
        formatTime(music.currentTime)
      }}</span>
      <span
        class="h-1.5 flex-1 overflow-hidden rounded-full bg-theme-100 dark:bg-zinc-900"
        aria-hidden="true"
      >
        <span
          class="block h-full rounded-full bg-theme-500 dark:bg-theme-400"
          :style="{ width: music.progress * 100 + '%' }"
        ></span>
      </span>
      <span>{{ formatTime(music.duration) }}</span>
    </div>

    <div class="flex items-center gap-1">
      <button
        type="button"
        :class="buttonClass"
        aria-label="上一首"
        data-testid="study-room-music-previous"
        :disabled="!music.loaded"
        @click="music.previous()"
      >
        <BackwardIcon class="size-4" />
      </button>
      <button
        type="button"
        class="flex size-8 items-center justify-center rounded-lg border-2 border-b-4 border-theme-700 bg-theme-500 text-white transition hover:bg-theme-600 focus-visible:ring-2 focus-visible:ring-theme-500 focus-visible:ring-offset-1 focus-visible:outline-none active:translate-y-px active:border-b-2 dark:border-theme-800 dark:bg-theme-600 dark:hover:bg-theme-500"
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
        :class="buttonClass"
        aria-label="下一首"
        data-testid="study-room-music-next"
        :disabled="!music.loaded"
        @click="music.next()"
      >
        <ForwardIcon class="size-4" />
      </button>
      <button
        type="button"
        :class="buttonClass"
        :aria-label="music.loaded ? '取出卡帶' : '選擇卡帶'"
        :aria-expanded="music.shelfOpen ? 'true' : 'false'"
        data-testid="study-room-music-eject"
        @click="music.eject()"
      >
        <svg class="size-4" viewBox="0 0 16 16" aria-hidden="true">
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
  </section>
</template>
