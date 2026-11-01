<script setup>
// The corkboard: published notes at the spots their authors picked, your own
// pending note (only you see it) marked 審核中, and a detail panel with a
// report button. Notes are buttons so the wall is reachable by keyboard.
import { computed, ref } from 'vue'

const props = defineProps({
  notes: { type: Array, required: true },
})

const emit = defineEmits(['report'])

const selectedId = ref(null)
const reported = ref(new Set())

const selected = computed(
  () => props.notes.find(note => note.id === selectedId.value) ?? null
)

function select(note) {
  selectedId.value = selectedId.value === note.id ? null : note.id
}

function report(note) {
  reported.value = new Set([...reported.value, note.id])
  emit('report', note)
}

function formatDate(iso) {
  return new Intl.DateTimeFormat('zh-TW', {
    month: 'numeric',
    day: 'numeric',
    timeZone: 'Asia/Taipei',
  }).format(new Date(iso))
}

function noteLabel(note) {
  const who = note.isMine ? '你的便利貼' : `${note.nickname ?? '同學'}的便利貼`
  const what = note.text ?? '一張手繪的圖'
  const pending = note.status !== 'published' ? '，審核中' : ''

  return `${who}：${what}${pending}`
}
</script>

<template>
  <section aria-labelledby="cafe-wall-heading" data-testid="cafe-wall">
    <h2
      id="cafe-wall-heading"
      tabindex="-1"
      accesskey="6"
      class="mb-1.5 text-xs font-semibold tracking-widest text-theme-700 dark:text-zinc-400"
    >
      便利貼牆
    </h2>

    <div
      class="relative aspect-4/3 w-full overflow-hidden rounded-xl border-[6px] border-amber-900/60 bg-amber-100 shadow-inner dark:border-amber-800/60 dark:bg-amber-950/40"
    >
      <p
        v-if="!notes.length"
        class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-amber-950/70 dark:text-amber-100/60"
        data-testid="cafe-wall-empty"
      >
        牆上還空空的，點杯飲料，當第一個貼便利貼的人吧。
      </p>

      <button
        v-for="note in notes"
        :key="note.id"
        type="button"
        class="absolute flex w-[22%] min-w-14 flex-col items-center justify-center overflow-hidden rounded-sm p-1 text-center shadow-md transition hover:z-10 hover:scale-110 focus-visible:z-10 focus-visible:scale-110"
        :class="[
          `note-paper-${note.paperColor}`,
          note.status !== 'published' ? 'opacity-70 ring-2 ring-amber-500' : '',
          selectedId === note.id ? 'z-10 scale-110 ring-2 ring-theme-800' : '',
        ]"
        :style="{
          left: `${note.x * 78}%`,
          top: `${note.y * 70}%`,
          transform: `rotate(${note.rotation}deg)`,
          aspectRatio: '1',
        }"
        :aria-label="noteLabel(note)"
        :aria-pressed="selectedId === note.id"
        :data-testid="`cafe-note-${note.id}`"
        @click="select(note)"
      >
        <img
          v-if="note.drawingUrl"
          :src="note.drawingUrl"
          alt=""
          class="size-full object-contain"
          loading="lazy"
        />
        <span
          v-if="note.text"
          class="line-clamp-3 text-[0.625rem] leading-tight font-medium break-words text-zinc-900"
          >{{ note.text }}</span
        >
        <span
          v-if="note.status !== 'published'"
          class="absolute right-0 bottom-0 rounded-tl bg-amber-500 px-1 text-[0.5625rem] font-bold text-white"
          >審核中</span
        >
      </button>
    </div>

    <div
      v-if="selected"
      class="mt-2 flex items-start gap-3 rounded-xl border-2 border-theme-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800"
      data-testid="cafe-note-detail"
    >
      <img
        v-if="selected.drawingUrl"
        :src="selected.drawingUrl"
        alt="手繪便利貼"
        class="size-28 shrink-0 rounded-md border border-theme-200 object-contain dark:border-zinc-700"
        :class="`note-paper-${selected.paperColor}`"
      />
      <div class="min-w-0 flex-1 text-sm">
        <p class="font-semibold text-theme-900 dark:text-zinc-100">
          {{ selected.emoji }} {{ selected.nickname ?? '同學' }}
          <span
            v-if="selected.publishedAt"
            class="text-xs font-normal text-theme-600 dark:text-zinc-400"
            >・{{ formatDate(selected.publishedAt) }}</span
          >
        </p>
        <p
          v-if="selected.text"
          class="mt-1 break-words text-theme-800 dark:text-zinc-200"
        >
          {{ selected.text }}
        </p>
        <p
          v-if="selected.status !== 'published'"
          class="mt-1 text-xs text-amber-800 dark:text-amber-300"
        >
          還在審核，通過後大家就看得到了。
        </p>
        <button
          v-if="selected.canReport"
          type="button"
          class="mt-2 text-xs text-theme-700 underline disabled:no-underline dark:text-zinc-400"
          :disabled="reported.has(selected.id)"
          data-testid="cafe-note-report"
          @click="report(selected)"
        >
          {{ reported.has(selected.id) ? '已回報，謝謝' : '回報這張便利貼' }}
        </button>
      </div>
    </div>
  </section>
</template>
