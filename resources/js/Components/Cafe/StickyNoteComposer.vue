<script setup>
// Buying a sticky note, in three steps inside a modal: draw it first (paper
// colour, pen strokes and/or a few words), then put it on the real wall with
// the other notes on it (tap the cork; the arrow buttons nudge it for keyboard
// users), then look at it once more and pay. The drawing pad stays mounted
// across the steps so going back keeps the strokes and the PNG is exported
// from the same canvas at the end. The server decides everything that
// matters (coffee today, one a day, acorns); this only shows what it says.
import { computed, ref } from 'vue'
import AcornIcon from '../Acorns/AcornIcon.vue'
import StickyNoteBoard from './StickyNoteBoard.vue'
import StickyNoteCanvas from './StickyNoteCanvas.vue'

const props = defineProps({
  price: { type: Number, required: true },
  balance: { type: Number, required: true },
  maxLength: { type: Number, default: 60 },
  lifetimeDays: { type: Number, default: 14 },
  // What is on the wall now, so the note is placed among real neighbours.
  notes: { type: Array, default: () => [] },
})

const emit = defineEmits(['posted', 'close'])

const COLORS = [
  { id: 'yellow', label: '黃色' },
  { id: 'pink', label: '粉紅' },
  { id: 'blue', label: '藍色' },
  { id: 'green', label: '綠色' },
  { id: 'orange', label: '橘色' },
]

const STEPS = [
  { id: 'draw', label: '畫便利貼' },
  { id: 'place', label: '選位置' },
  { id: 'confirm', label: '確認' },
]

const step = ref('draw')
const text = ref('')
const paperColor = ref('yellow')
const x = ref(0.5)
const y = ref(0.5)
const rotation = ref(Math.round(Math.random() * 10) - 5)
const pad = ref(null)
const drew = ref(false)
const imageUrl = ref(null)
const busy = ref(false)
const errorMessage = ref(null)

const stepIndex = computed(() =>
  STEPS.findIndex(item => item.id === step.value)
)

const hasContent = computed(() => text.value.trim() !== '' || drew.value)

const canPay = computed(() => props.balance >= props.price)

const draft = computed(() => ({
  paperColor: paperColor.value,
  rotation: rotation.value,
  x: x.value,
  y: y.value,
  imageUrl: imageUrl.value,
  text: text.value.trim() || null,
}))

function goToPlace() {
  if (!hasContent.value) {
    return
  }

  imageUrl.value = drew.value ? pad.value.toDataURL() : null
  step.value = 'place'
}

function place(position) {
  x.value = position.x
  y.value = position.y
}

function nudge(dx, dy) {
  x.value = clamp(x.value + dx)
  y.value = clamp(y.value + dy)
}

function clamp(value) {
  return Math.min(1, Math.max(0, Math.round(value * 1000) / 1000))
}

function firstError(error) {
  const errors = error.response?.data?.errors

  if (errors) {
    return Object.values(errors)[0][0]
  }

  return error.response?.data?.message ?? '網路好像不太穩定，請稍後再試一次。'
}

async function submit() {
  if (busy.value || !canPay.value) {
    return
  }

  busy.value = true
  errorMessage.value = null

  const form = new FormData()
  form.append('paperColor', paperColor.value)
  form.append('x', String(x.value))
  form.append('y', String(y.value))
  form.append('rotation', String(rotation.value))

  if (text.value.trim() !== '') {
    form.append('text', text.value.trim())
  }

  if (drew.value) {
    const blob = await pad.value.toBlob()
    form.append('drawing', blob, 'note.png')
  }

  try {
    const response = await window.axios.post('/cafe/notes', form)
    emit('posted', response.data)
  } catch (error) {
    errorMessage.value = firstError(error)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="space-y-3" data-testid="cafe-note-composer" :data-step="step">
    <ol class="flex items-center gap-1 text-xs" aria-label="步驟">
      <li
        v-for="(item, index) in STEPS"
        :key="item.id"
        class="flex items-center gap-1"
        :aria-current="item.id === step ? 'step' : null"
      >
        <span
          class="flex size-5 items-center justify-center rounded-full border-2 text-[0.625rem] font-bold"
          :class="
            index <= stepIndex
              ? 'border-theme-800 bg-theme-800 text-white dark:border-amber-400 dark:bg-amber-400 dark:text-zinc-900'
              : 'border-theme-300 text-theme-600 dark:border-zinc-600 dark:text-zinc-400'
          "
          >{{ index + 1 }}</span
        >
        <span
          :class="
            item.id === step
              ? 'font-semibold text-theme-900 dark:text-zinc-100'
              : 'text-theme-600 dark:text-zinc-400'
          "
          >{{ item.label }}</span
        >
        <span
          v-if="index < STEPS.length - 1"
          class="mx-1 h-px w-4 bg-theme-300 dark:bg-zinc-600"
          aria-hidden="true"
        ></span>
      </li>
    </ol>

    <!-- Step 1: draw. Kept mounted (v-show) so the canvas survives going back. -->
    <div v-show="step === 'draw'" class="space-y-3">
      <fieldset>
        <legend class="mb-1 text-xs text-theme-700 dark:text-zinc-400">
          紙的顏色
        </legend>
        <div class="flex gap-2">
          <label
            v-for="color in COLORS"
            :key="color.id"
            class="cursor-pointer"
            :title="color.label"
          >
            <input
              v-model="paperColor"
              type="radio"
              :value="color.id"
              class="peer sr-only"
              :data-testid="`note-color-${color.id}`"
            />
            <span
              class="block size-8 rounded-md border-2 border-theme-200 peer-checked:border-theme-800 peer-focus-visible:outline-2 dark:border-zinc-600 dark:peer-checked:border-amber-400"
              :class="`note-paper-${color.id}`"
            ></span>
            <span class="sr-only">{{ color.label }}</span>
          </label>
        </div>
      </fieldset>

      <StickyNoteCanvas
        ref="pad"
        :paper-color="paperColor"
        @change="drew = $event"
      />

      <div>
        <label
          for="cafe-note-text"
          class="mb-1 block text-xs text-theme-700 dark:text-zinc-400"
        >
          想說的話（也可以只畫圖）
        </label>
        <input
          id="cafe-note-text"
          v-model="text"
          type="text"
          :maxlength="maxLength"
          class="w-full rounded-md border-2 border-theme-200 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-900"
          data-testid="cafe-note-text"
        />
        <p
          class="mt-0.5 text-right text-[0.6875rem] text-theme-600 dark:text-zinc-400"
        >
          {{ text.length }}/{{ maxLength }}
        </p>
      </div>
    </div>

    <!-- Step 2: place it on the wall as it is now. -->
    <div v-if="step === 'place'" class="space-y-2">
      <p class="text-xs text-theme-700 dark:text-zinc-400">
        點一下牆面，決定貼在哪裡。
      </p>
      <div
        class="rounded-xl border-[6px] border-amber-900/60 bg-amber-900/60 p-0.5 dark:border-amber-800/60 dark:bg-amber-800/60"
        data-testid="cafe-note-placement"
      >
        <StickyNoteBoard :notes="notes" :draft="draft" placing @place="place" />
      </div>
      <div class="flex justify-center gap-1">
        <button
          type="button"
          class="rounded border border-theme-300 px-2 py-0.5 text-xs dark:border-zinc-600"
          @click="nudge(-0.05, 0)"
        >
          ← 左移
        </button>
        <button
          type="button"
          class="rounded border border-theme-300 px-2 py-0.5 text-xs dark:border-zinc-600"
          @click="nudge(0, -0.05)"
        >
          ↑ 上移
        </button>
        <button
          type="button"
          class="rounded border border-theme-300 px-2 py-0.5 text-xs dark:border-zinc-600"
          @click="nudge(0, 0.05)"
        >
          ↓ 下移
        </button>
        <button
          type="button"
          class="rounded border border-theme-300 px-2 py-0.5 text-xs dark:border-zinc-600"
          @click="nudge(0.05, 0)"
        >
          → 右移
        </button>
      </div>
    </div>

    <!-- Step 3: one more look, then pay. -->
    <div v-if="step === 'confirm'" class="space-y-2">
      <div
        class="rounded-xl border-[6px] border-amber-900/60 bg-amber-900/60 p-0.5 dark:border-amber-800/60 dark:bg-amber-800/60"
        data-testid="cafe-note-preview"
      >
        <StickyNoteBoard :notes="notes" :draft="draft" />
      </div>
      <p class="text-sm text-theme-800 dark:text-zinc-200">
        便利貼會先經過檢查，通過後貼在牆上
        <span class="whitespace-nowrap">{{ lifetimeDays }} 天</span>。花
        <span class="inline-flex items-center gap-0.5 font-semibold"
          ><AcornIcon /> {{ price }}</span
        >
        顆橡實，你有 {{ balance }} 顆。
      </p>
    </div>

    <p
      v-if="errorMessage"
      role="alert"
      class="text-sm text-red-800 dark:text-red-300"
      data-testid="cafe-note-error"
    >
      {{ errorMessage }}
    </p>

    <div class="flex flex-wrap items-center gap-2 pt-1">
      <button
        v-if="step === 'draw'"
        type="button"
        class="rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1.5 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 disabled:border-theme-300 disabled:bg-theme-200 disabled:text-theme-600 dark:border-theme-900 dark:bg-theme-700 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-700 dark:disabled:text-zinc-400"
        :disabled="!hasContent"
        data-testid="cafe-note-next"
        @click="goToPlace"
      >
        下一步：選位置
      </button>
      <button
        v-if="step === 'place'"
        type="button"
        class="rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1.5 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 dark:border-theme-900 dark:bg-theme-700"
        data-testid="cafe-note-place-next"
        @click="step = 'confirm'"
      >
        就貼這裡
      </button>
      <button
        v-if="step === 'confirm'"
        type="button"
        class="flex items-center gap-1 rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1.5 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 disabled:border-theme-300 disabled:bg-theme-200 disabled:text-theme-600 dark:border-theme-900 dark:bg-theme-700 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-700 dark:disabled:text-zinc-400"
        :disabled="busy || !canPay"
        data-testid="cafe-note-submit"
        @click="submit"
      >
        貼上牆 <AcornIcon /> {{ price }}
      </button>
      <button
        v-if="step !== 'draw'"
        type="button"
        class="rounded-lg border-2 border-b-4 border-theme-300 bg-white px-3 py-1.5 text-sm font-medium text-theme-900 transition active:translate-y-px active:border-b-2 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
        data-testid="cafe-note-back"
        @click="step = step === 'confirm' ? 'place' : 'draw'"
      >
        上一步
      </button>
      <button
        type="button"
        class="text-sm text-theme-700 underline dark:text-zinc-400"
        @click="emit('close')"
      >
        先不貼了
      </button>
      <span v-if="!canPay" class="text-xs text-theme-700 dark:text-zinc-400">
        橡實不夠，目前有 {{ balance }} 顆
      </span>
    </div>
  </div>
</template>
