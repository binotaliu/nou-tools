<script setup>
// Above the seats: 快速入座 (take the first free seat without hunting for
// one), the 平面圖 / 清單 switch, and 語音提示 (what the room reads out on its
// own; not in the preview, which has no timer or neighbours). The list view
// reads the same room as a table, for screen readers and anyone who'd
// rather scan than look.
import { onMounted, onUnmounted, ref, useId } from 'vue'
import {
  ListBulletIcon,
  MapIcon,
  SparklesIcon,
  SpeakerWaveIcon,
} from '@heroicons/vue/24/outline'
import {
  INTERVAL_OPTIONS,
  ROOM_OPTIONS,
} from '../../Composables/useStudyRoomVoiceSettings'

const props = defineProps({
  socket: { type: Object, required: true },
  grid: { type: Object, required: true },
  announcer: { type: Object, required: true },
  view: { type: String, required: true },
  voiceSettings: { type: Object, default: null },
})

const emit = defineEmits(['update:view'])

function takeFirstFreeSeat() {
  if (props.socket.busySeatCode !== null) {
    return
  }

  const seat = props.grid.firstFreeSeat()

  if (!seat) {
    props.announcer.say('目前沒有空位', { assertive: true })
    return
  }

  props.socket.take(seat.code)
}

const voiceOpen = ref(false)
const voiceRoot = ref(null)
const voicePanelId = useId()

function closeVoiceOnOutsideClick(event) {
  if (voiceRoot.value && !voiceRoot.value.contains(event.target)) {
    voiceOpen.value = false
  }
}

function closeVoiceOnEscape(event) {
  if (event.key === 'Escape' && voiceOpen.value) {
    voiceOpen.value = false
    voiceRoot.value?.querySelector('button')?.focus()
  }
}

onMounted(() => {
  document.addEventListener('click', closeVoiceOnOutsideClick)
})

onUnmounted(() => {
  document.removeEventListener('click', closeVoiceOnOutsideClick)
})

const selectClass =
  'w-full rounded-lg border border-theme-200 bg-white px-3 py-2 text-base sm:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100'

const viewButtonClass =
  'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition aria-pressed:bg-theme-700 aria-pressed:text-white dark:aria-pressed:bg-theme-500 dark:aria-pressed:text-zinc-950'
</script>

<template>
  <div
    role="group"
    aria-label="座位工具"
    class="flex flex-wrap items-center justify-between gap-3"
    data-testid="study-room-toolbar"
  >
    <button
      v-if="!socket.heldSeatCode"
      type="button"
      :aria-disabled="socket.busySeatCode !== null ? 'true' : null"
      class="inline-flex items-center gap-1.5 rounded-lg bg-theme-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-theme-800 aria-disabled:opacity-50 dark:bg-theme-500 dark:text-zinc-950 dark:hover:bg-theme-400"
      accesskey="4"
      data-testid="study-room-quick-seat"
      @click="takeFirstFreeSeat()"
    >
      <SparklesIcon class="size-4" />
      快速入座
    </button>
    <span v-else></span>

    <div class="flex flex-wrap items-center gap-2">
      <div
        v-if="voiceSettings"
        ref="voiceRoot"
        class="relative"
        @keydown="closeVoiceOnEscape"
      >
        <button
          type="button"
          :aria-expanded="voiceOpen ? 'true' : 'false'"
          :aria-controls="voicePanelId"
          class="inline-flex items-center gap-1.5 rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm font-medium text-theme-800 transition hover:border-theme-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
          data-testid="study-room-voice-settings-toggle"
          @click="voiceOpen = !voiceOpen"
        >
          <SpeakerWaveIcon class="size-4" />
          語音提示
        </button>

        <div
          v-show="voiceOpen"
          :id="voicePanelId"
          class="absolute right-0 z-30 mt-2 w-72 space-y-3 rounded-xl border border-theme-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
          data-testid="study-room-voice-settings"
        >
          <p class="text-xs text-theme-700 dark:text-zinc-400">
            給螢幕閱讀器使用者：開始、暫停、時間到等變化一律會念出來，下面兩項則可自行選擇。設定只存在這個瀏覽器。
          </p>
          <label class="block">
            <span
              class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
              >計時報時</span
            >
            <select
              v-model.number="voiceSettings.intervalMinutes"
              :class="selectClass"
              data-testid="study-room-voice-interval"
            >
              <option
                v-for="option in INTERVAL_OPTIONS"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </label>
          <label class="block">
            <span
              class="mb-1 block text-xs font-medium text-theme-700 dark:text-zinc-400"
              >有人入座或離開時</span
            >
            <select
              v-model="voiceSettings.roomActivity"
              :class="selectClass"
              data-testid="study-room-voice-room"
            >
              <option
                v-for="option in ROOM_OPTIONS"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </label>
        </div>
      </div>

      <div
        role="group"
        aria-label="座位顯示方式"
        class="inline-flex rounded-lg border border-theme-200 bg-white p-0.5 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <button
          type="button"
          :aria-pressed="view === 'map' ? 'true' : 'false'"
          :class="viewButtonClass"
          data-testid="study-room-view-map"
          @click="emit('update:view', 'map')"
        >
          <MapIcon class="size-4" />
          平面圖
        </button>
        <button
          type="button"
          :aria-pressed="view === 'list' ? 'true' : 'false'"
          :class="viewButtonClass"
          data-testid="study-room-view-list"
          @click="emit('update:view', 'list')"
        >
          <ListBulletIcon class="size-4" />
          清單
        </button>
      </div>
    </div>
  </div>
</template>
