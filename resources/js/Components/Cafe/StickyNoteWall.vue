<script setup>
// The corkboard up close, shown in a modal when the wall on the café's back
// wall is clicked: published notes at the spots their authors picked, your own
// pending note (only you see it) marked 審核中, and a detail panel with a
// report button. Notes are buttons so the wall is reachable by keyboard.
import { computed, ref } from 'vue'
import StickyNoteBoard from './StickyNoteBoard.vue'

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
</script>

<template>
  <div data-testid="cafe-wall">
    <div
      class="rounded-xl border-[6px] border-amber-900/60 bg-amber-900/60 p-0.5 dark:border-amber-800/60 dark:bg-amber-800/60"
    >
      <StickyNoteBoard
        :notes="notes"
        interactive
        :selected-id="selectedId"
        @select="select"
      />
    </div>

    <p class="mt-2 text-xs text-theme-700 dark:text-zinc-400">
      點擊便利貼以放大。
    </p>

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
          松鼠店長好像還沒幫你把這張便利貼貼上去。
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
  </div>
</template>
