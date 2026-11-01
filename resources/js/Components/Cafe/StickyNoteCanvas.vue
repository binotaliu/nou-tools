<script setup>
// A 480×480 drawing pad for a sticky note. Pointer events make it work the
// same for mouse, touch and pen; the bitmap is exactly the size the server
// accepts and is exported as a transparent PNG (the paper colour is CSS).
// Drawing needs a pointer, so the composer always offers text as well.
import { computed, ref } from 'vue'

const SIZE = 480
const MAX_UNDO = 12

const PENS = [
  { id: 'ink', label: '黑筆', color: '#2b2118', width: 5 },
  { id: 'red', label: '紅筆', color: '#d6453d', width: 5 },
  { id: 'blue', label: '藍筆', color: '#2f6fd1', width: 5 },
  { id: 'marker', label: '粗麥克筆', color: '#2b2118', width: 16 },
]

defineProps({
  paperColor: { type: String, required: true },
})

const emit = defineEmits(['change'])

const canvas = ref(null)
const tool = ref('ink')
const undoStack = ref([])
const hasContent = ref(false)

let drawing = false
let last = null

const currentPen = computed(() => PENS.find(pen => pen.id === tool.value))

function context() {
  return canvas.value.getContext('2d', { willReadFrequently: true })
}

function point(event) {
  const rect = canvas.value.getBoundingClientRect()

  return {
    x: ((event.clientX - rect.left) * SIZE) / rect.width,
    y: ((event.clientY - rect.top) * SIZE) / rect.height,
  }
}

function snapshot() {
  undoStack.value.push(context().getImageData(0, 0, SIZE, SIZE))

  if (undoStack.value.length > MAX_UNDO) {
    undoStack.value.shift()
  }
}

function stroke(from, to) {
  const ctx = context()
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'

  if (tool.value === 'eraser') {
    ctx.globalCompositeOperation = 'destination-out'
    ctx.lineWidth = 24
  } else {
    ctx.globalCompositeOperation = 'source-over'
    ctx.strokeStyle = currentPen.value.color
    ctx.lineWidth = currentPen.value.width
  }

  ctx.beginPath()
  ctx.moveTo(from.x, from.y)
  ctx.lineTo(to.x, to.y)
  ctx.stroke()
}

function start(event) {
  event.preventDefault()
  try {
    canvas.value.setPointerCapture(event.pointerId)
  } catch {
    // Capture is a nicety (strokes keep going outside the pad); drawing works without it.
  }

  snapshot()
  drawing = true
  last = point(event)
  stroke(last, last)
  hasContent.value = true
}

function move(event) {
  if (!drawing) {
    return
  }

  const next = point(event)
  stroke(last, next)
  last = next
}

function end() {
  if (!drawing) {
    return
  }

  drawing = false
  emit('change', hasContent.value)
}

function undo() {
  const previous = undoStack.value.pop()

  if (!previous) {
    return
  }

  context().putImageData(previous, 0, 0)
  hasContent.value = undoStack.value.length > 0
  emit('change', hasContent.value)
}

function clear() {
  if (!hasContent.value) {
    return
  }

  snapshot()
  context().clearRect(0, 0, SIZE, SIZE)
  hasContent.value = false
  emit('change', false)
}

function toBlob() {
  return new Promise(resolve => canvas.value.toBlob(resolve, 'image/png'))
}

defineExpose({ toBlob, hasContent })
</script>

<template>
  <div data-testid="sticky-note-pad">
    <div
      class="mx-auto aspect-square w-full max-w-xs overflow-hidden rounded-md border-2 border-theme-300 shadow-inner dark:border-zinc-600"
      :class="`note-paper-${paperColor}`"
    >
      <canvas
        ref="canvas"
        :width="SIZE"
        :height="SIZE"
        class="size-full cursor-crosshair touch-none"
        role="img"
        aria-label="便利貼畫布，需要用滑鼠或觸控作畫"
        data-testid="sticky-note-canvas"
        @pointerdown="start"
        @pointermove="move"
        @pointerup="end"
        @pointercancel="end"
      ></canvas>
    </div>

    <div class="mt-2 flex flex-wrap items-center justify-center gap-1.5">
      <button
        v-for="pen in PENS"
        :key="pen.id"
        type="button"
        class="rounded-md border-2 px-2 py-1 text-xs font-medium"
        :class="
          tool === pen.id
            ? 'border-theme-700 bg-theme-100 dark:border-amber-400 dark:bg-zinc-700'
            : 'border-theme-200 dark:border-zinc-700'
        "
        :aria-pressed="tool === pen.id"
        @click="tool = pen.id"
      >
        <span
          class="mr-1 inline-block size-2.5 rounded-full align-middle"
          :style="{ backgroundColor: pen.color }"
          aria-hidden="true"
        ></span
        >{{ pen.label }}
      </button>
      <button
        type="button"
        class="rounded-md border-2 px-2 py-1 text-xs font-medium"
        :class="
          tool === 'eraser'
            ? 'border-theme-700 bg-theme-100 dark:border-amber-400 dark:bg-zinc-700'
            : 'border-theme-200 dark:border-zinc-700'
        "
        :aria-pressed="tool === 'eraser'"
        @click="tool = 'eraser'"
      >
        橡皮擦
      </button>
      <button
        type="button"
        class="rounded-md border-2 border-theme-200 px-2 py-1 text-xs font-medium disabled:opacity-40 dark:border-zinc-700"
        :disabled="!undoStack.length"
        data-testid="sticky-note-undo"
        @click="undo"
      >
        復原
      </button>
      <button
        type="button"
        class="rounded-md border-2 border-theme-200 px-2 py-1 text-xs font-medium disabled:opacity-40 dark:border-zinc-700"
        :disabled="!hasContent"
        data-testid="sticky-note-clear"
        @click="clear"
      >
        清除
      </button>
    </div>
  </div>
</template>
