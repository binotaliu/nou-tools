<script setup>
// The entrance wall — a window onto the garden scene with the cassette
// player under it, a wall clock running on Taipei time, the announcement
// board, and the viewer's own nameplate (click to edit nickname/emoji, or
// open the stats modal).
import { ref } from 'vue'
import { ChartBarIcon, PencilIcon } from '@heroicons/vue/24/outline'
import CassettePlayer from './CassettePlayer.vue'
import GardenScene from './GardenScene.vue'
import useMarkdownContainers from '../../Composables/useMarkdownContainers'

const props = defineProps({
  sky: { type: Object, required: true },
  profile: { type: Object, required: true },
  music: { type: Object, required: true },
  announcementHtml: { type: String, required: true },
  yourFocusSecondsToday: { type: Number, required: true },
})

// The announcement is admin-authored Markdown run through the same
// converter as articles (RenderStudyRoomAnnouncement), so it can contain
// the same interactive containers.
const announcementRoot = ref(null)

useMarkdownContainers(announcementRoot, [() => props.announcementHtml])
</script>

<template>
  <div
    class="relative overflow-hidden rounded-2xl border-[6px] border-theme-300 bg-theme-100/60 shadow-sm dark:border-zinc-600 dark:bg-zinc-900"
    data-testid="study-room-wall"
  >
    <div
      class="pointer-events-none absolute inset-x-0 bottom-0 h-3 border-t-2 border-theme-300 bg-theme-200 dark:border-zinc-600 dark:bg-zinc-800"
      aria-hidden="true"
    ></div>

    <!--
      Phone: window, then player and clock share a row, then the announcement.
      sm+: window over the player in the first column, clock beside them, and
      the announcement spanning both rows. One player instance serves both.
    -->
    <div
      class="relative grid grid-cols-[1fr_auto] gap-x-4 gap-y-5 p-4 pb-7 sm:grid-cols-[14rem_auto_1fr] sm:items-start sm:gap-x-6 sm:gap-y-3 sm:p-6 sm:pb-9 md:grid-cols-[16rem_auto_1fr]"
    >
      <div
        class="relative col-span-2 min-w-0 sm:col-span-1 sm:col-start-1 sm:row-start-1"
      >
        <div
          class="pointer-events-none absolute inset-x-0 top-6 h-32 transition-[background] duration-1000"
          :style="sky.windowLightStyle()"
          aria-hidden="true"
        ></div>

        <div
          class="relative h-40 overflow-hidden rounded-lg border-4 border-theme-300 shadow-sm sm:h-44 dark:border-zinc-600"
        >
          <GardenScene :sky="sky" scene-class="absolute inset-0" />

          <div
            class="pointer-events-none absolute inset-y-0 left-1/2 w-1.5 -translate-x-1/2 bg-theme-300 dark:bg-zinc-600"
            aria-hidden="true"
          ></div>
          <div
            class="pointer-events-none absolute inset-x-0 top-[46%] h-1.5 bg-theme-300 dark:bg-zinc-600"
            aria-hidden="true"
          ></div>
        </div>

        <div class="relative">
          <div
            class="-mx-1.5 h-2 rounded-full bg-theme-300 dark:bg-zinc-600"
            aria-hidden="true"
          ></div>
          <span
            class="absolute -top-3.5 right-4 text-base leading-none"
            aria-hidden="true"
            >🪴</span
          >
        </div>
      </div>

      <CassettePlayer
        v-if="music.available"
        :music="music"
        class="self-center sm:col-start-1 sm:row-start-2"
      />

      <div
        class="flex shrink-0 flex-col items-center gap-1.5 self-center sm:col-start-2 sm:row-start-1 sm:mt-2 sm:self-start"
        :class="
          music.available ? '' : 'max-sm:col-span-2 max-sm:justify-self-center'
        "
        data-testid="study-room-clock"
      >
        <div
          class="relative size-20 rounded-full border-2 border-b-4 border-theme-300 bg-white shadow-sm sm:size-24 dark:border-zinc-600 dark:bg-zinc-800"
          aria-hidden="true"
        >
          <span
            v-for="tick in 12"
            :key="tick"
            class="absolute top-0 left-1/2 h-1/2 w-0.5 origin-bottom"
            :style="sky.clockTickStyle(tick)"
          >
            <span
              class="block w-full rounded-full bg-theme-300 dark:bg-zinc-600"
              :class="sky.clockTickClass(tick)"
            ></span>
          </span>

          <span
            class="absolute bottom-1/2 left-1/2 h-[26%] w-1 origin-bottom rounded-full bg-theme-700 dark:bg-zinc-300"
            :style="sky.clockHandStyle('hour')"
            data-testid="study-room-clock-hour-hand"
          ></span>
          <span
            class="absolute bottom-1/2 left-1/2 h-[36%] w-0.5 origin-bottom rounded-full bg-theme-700 dark:bg-zinc-300"
            :style="sky.clockHandStyle('minute')"
            data-testid="study-room-clock-minute-hand"
          ></span>
          <span
            class="absolute bottom-1/2 left-1/2 h-[40%] w-px origin-bottom rounded-full bg-amber-500"
            :style="sky.clockHandStyle('second')"
          ></span>
          <span
            class="absolute top-1/2 left-1/2 size-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-theme-700 dark:bg-zinc-300"
          ></span>
        </div>

        <p class="text-center">
          <span
            class="block font-mono text-sm leading-tight font-semibold text-theme-800 tabular-nums dark:text-zinc-200"
            >{{ sky.clockTimeLabel() }}</span
          >
          <span class="block text-[10px] text-theme-700 dark:text-zinc-400">{{
            sky.clockDateLabel()
          }}</span>
        </p>
      </div>

      <div
        class="col-span-2 flex min-w-0 flex-col gap-3 sm:col-span-1 sm:col-start-3 sm:row-span-2 sm:row-start-1"
      >
        <div
          class="rounded-xl border-2 border-b-4 border-theme-300 bg-theme-200 p-3 shadow-sm dark:border-zinc-600 dark:bg-zinc-800"
          data-testid="study-room-announcement"
        >
          <div
            class="relative rounded-lg border border-theme-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
          >
            <span
              class="absolute -top-1 left-5 size-2 rounded-full bg-rose-400 shadow-sm"
              aria-hidden="true"
            ></span>
            <span
              class="absolute -top-1 right-5 size-2 rounded-full bg-sky-400 shadow-sm"
              aria-hidden="true"
            ></span>

            <h2
              class="mb-1.5 text-sm font-semibold text-theme-900 dark:text-zinc-100"
            >
              公告板
            </h2>
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div
              ref="announcementRoot"
              tabindex="0"
              class="prose prose-sm max-h-36 max-w-none overflow-y-auto prose-theme dark:prose-invert"
              v-html="announcementHtml"
            ></div>
          </div>
        </div>

        <div
          class="relative -rotate-1 cursor-pointer rounded-lg border-2 border-b-4 border-theme-300 bg-white px-4 py-2.5 shadow-sm transition hover:rotate-0 hover:border-theme-400 dark:border-zinc-600 dark:bg-zinc-800 dark:hover:border-zinc-500"
          data-testid="study-room-personal-info"
          @click="profile.openPersonalInfo()"
        >
          <span
            class="absolute -top-1 left-1/2 size-2 -translate-x-1/2 rounded-full bg-amber-400 shadow-sm"
            aria-hidden="true"
          ></span>

          <div class="flex items-center gap-3">
            <span class="text-2xl">{{ profile.emoji }}</span>
            <div class="min-w-0 flex-1">
              <p
                class="truncate text-sm font-semibold text-theme-900 dark:text-zinc-100"
              >
                {{ profile.nickname || '尚未設定暱稱' }}
              </p>
              <p class="text-xs text-theme-700 dark:text-zinc-400">
                今天專注了
                {{ profile.formatDurationLabel(yourFocusSecondsToday) }}
              </p>
            </div>
            <button
              type="button"
              aria-label="檢視專注紀錄與統計"
              data-testid="study-room-personal-info-stats"
              class="shrink-0 rounded-full p-1.5 text-theme-700 transition hover:bg-theme-100 hover:text-theme-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
              @click.stop="profile.openStats()"
            >
              <ChartBarIcon class="size-4" />
            </button>
            <button
              type="button"
              aria-label="編輯個人資料"
              data-testid="study-room-personal-info-edit"
              class="shrink-0 rounded-full p-1.5 text-theme-700 transition hover:bg-theme-100 hover:text-theme-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
              @click.stop="profile.openPersonalInfo()"
            >
              <PencilIcon class="size-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
