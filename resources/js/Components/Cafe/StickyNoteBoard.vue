<script setup>
// The cork surface with the notes pinned where their authors put them. It is
// drawn in four places: as a thumbnail on the café's back wall, full size in
// the wall modal (`interactive`, notes are buttons), and twice in the
// composer, where a `draft` note sits on it and, in `placing` mode, a click
// on the cork moves the draft there.
//
// Positions: `x`/`y` are 0‒1 and give the note's top-left corner within the
// range that keeps a 22%-wide square note fully on the board.
import { computed } from 'vue'

const NOTE_WIDTH = 0.22
const RANGE_X = 1 - NOTE_WIDTH
// The board is 4:3, so a square note is 22% × 4/3 of the height.
const NOTE_HEIGHT = NOTE_WIDTH * (4 / 3)
const RANGE_Y = 1 - NOTE_HEIGHT

const props = defineProps({
  notes: { type: Array, required: true },
  interactive: { type: Boolean, default: false },
  selectedId: { type: Number, default: null },
  // {paperColor, rotation, x, y, imageUrl, text} shown on top of the notes.
  draft: { type: Object, default: null },
  placing: { type: Boolean, default: false },
  // Smaller text and no empty-board message, for the thumbnail.
  compact: { type: Boolean, default: false },
})

const emit = defineEmits(['select', 'place'])

const empty = computed(() => !props.notes.length && !props.draft)

function noteStyle(note) {
  return {
    left: `${note.x * RANGE_X * 100}%`,
    top: `${note.y * RANGE_Y * 100}%`,
    transform: `rotate(${note.rotation}deg)`,
  }
}

function noteLabel(note) {
  const who = note.isMine ? '你的便利貼' : `${note.nickname ?? '同學'}的便利貼`
  const what = note.text ?? '一張手繪的圖'
  const pending = note.status !== 'published' ? '，審核中' : ''

  return `${who}：${what}${pending}`
}

function clamp(value) {
  return Math.min(1, Math.max(0, Math.round(value * 1000) / 1000))
}

// Centres the draft under the pointer.
function placeAt(event) {
  if (!props.placing) {
    return
  }

  const rect = event.currentTarget.getBoundingClientRect()
  const fx = (event.clientX - rect.left) / rect.width
  const fy = (event.clientY - rect.top) / rect.height

  emit('place', {
    x: clamp((fx - NOTE_WIDTH / 2) / RANGE_X),
    y: clamp((fy - NOTE_HEIGHT / 2) / RANGE_Y),
  })
}
</script>

<template>
  <div
    class="relative aspect-4/3 w-full overflow-hidden rounded-md bg-amber-200/90 bg-[radial-gradient(circle_at_1px_1px,rgba(120,72,20,0.18)_1px,transparent_0)] bg-[length:10px_10px] shadow-inner dark:bg-amber-900/50"
    :class="placing ? 'cursor-crosshair' : ''"
    :data-testid="interactive ? 'cafe-wall-board' : null"
    @click="placeAt"
  >
    <p
      v-if="empty && !compact"
      class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-amber-950/70 dark:text-amber-100/60"
      :data-testid="interactive ? 'cafe-wall-empty' : null"
    >
      牆上還空空的，歡迎購買便利貼。
    </p>

    <component
      :is="interactive ? 'button' : 'span'"
      v-for="note in notes"
      :key="note.id"
      :type="interactive ? 'button' : null"
      class="absolute flex aspect-square w-[22%] flex-col items-center justify-center overflow-hidden rounded-sm p-[3%] text-center shadow-md transition"
      :class="[
        `note-paper-${note.paperColor}`,
        note.status !== 'published' ? 'opacity-70 ring-2 ring-amber-500' : '',
        interactive
          ? 'hover:z-10 hover:scale-110 focus-visible:z-10 focus-visible:scale-110'
          : 'pointer-events-none',
        selectedId === note.id ? 'z-10 scale-110 ring-2 ring-theme-800' : '',
        placing ? 'opacity-80' : '',
      ]"
      :style="noteStyle(note)"
      :aria-label="interactive ? noteLabel(note) : null"
      :aria-pressed="interactive ? selectedId === note.id : null"
      :aria-hidden="interactive ? null : 'true'"
      :data-testid="interactive ? `cafe-note-${note.id}` : null"
      @click.stop="interactive && emit('select', note)"
    >
      <span
        class="pointer-events-none absolute top-[4%] left-1/2 size-[9%] -translate-x-1/2 rounded-full bg-red-500 shadow-sm"
        aria-hidden="true"
      ></span>
      <img
        v-if="note.drawingUrl"
        :src="note.drawingUrl"
        alt=""
        class="min-h-0 w-full flex-1 object-contain"
        loading="lazy"
      />
      <span
        v-if="note.text"
        class="line-clamp-3 leading-tight font-medium break-words text-zinc-900"
        :class="compact ? 'text-[0.4rem]' : 'text-[0.625rem]'"
        >{{ note.text }}</span
      >
      <span
        v-if="note.status !== 'published' && !compact"
        class="absolute right-0 bottom-0 rounded-tl bg-amber-500 px-1 text-[0.5625rem] font-bold text-white"
        >審核中</span
      >
    </component>

    <span
      v-if="draft"
      class="pointer-events-none absolute z-20 flex aspect-square w-[22%] flex-col items-center justify-center overflow-hidden rounded-sm p-[3%] text-center shadow-lg ring-2 ring-theme-800 transition-[left,top] duration-150 dark:ring-amber-300"
      :class="`note-paper-${draft.paperColor}`"
      :style="noteStyle(draft)"
      aria-hidden="true"
      data-testid="cafe-note-draft"
    >
      <span
        class="absolute top-[4%] left-1/2 size-[9%] -translate-x-1/2 rounded-full bg-red-500 shadow-sm"
      ></span>
      <img
        v-if="draft.imageUrl"
        :src="draft.imageUrl"
        alt=""
        class="min-h-0 w-full flex-1 object-contain"
      />
      <span
        v-if="draft.text"
        class="line-clamp-3 text-[0.625rem] leading-tight font-medium break-words text-zinc-900"
        >{{ draft.text }}</span
      >
    </span>
  </div>
</template>
