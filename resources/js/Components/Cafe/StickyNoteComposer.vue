<script setup>
// Buying and writing a sticky note: pick a paper colour, write a few words
// and/or draw, tap where it goes on the wall (the arrow buttons nudge it for
// keyboard users), then pay. The server decides everything that matters
// (coffee today, one a day, acorns); this only shows what it says.
import { computed, ref } from 'vue'
import AcornIcon from '../Acorns/AcornIcon.vue'
import StickyNoteCanvas from './StickyNoteCanvas.vue'

const props = defineProps({
  price: { type: Number, required: true },
  balance: { type: Number, required: true },
  maxLength: { type: Number, default: 60 },
})

const emit = defineEmits(['posted', 'close'])

const COLORS = [
  { id: 'yellow', label: '黃色' },
  { id: 'pink', label: '粉紅' },
  { id: 'blue', label: '藍色' },
  { id: 'green', label: '綠色' },
  { id: 'orange', label: '橘色' },
]

const text = ref('')
const paperColor = ref('yellow')
const x = ref(0.5)
const y = ref(0.5)
const rotation = ref(Math.round(Math.random() * 10) - 5)
const pad = ref(null)
const drew = ref(false)
const busy = ref(false)
const errorMessage = ref(null)
const wall = ref(null)

const canSubmit = computed(
  () =>
    !busy.value &&
    props.balance >= props.price &&
    (text.value.trim() !== '' || drew.value)
)

function placeAt(event) {
  const rect = wall.value.getBoundingClientRect()
  x.value = clamp((event.clientX - rect.left) / rect.width)
  y.value = clamp((event.clientY - rect.top) / rect.height)
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
  <section
    class="space-y-3 rounded-xl border-2 border-b-4 border-theme-300 bg-white p-3 dark:border-zinc-600 dark:bg-zinc-800"
    aria-labelledby="cafe-note-heading"
    data-testid="cafe-note-composer"
  >
    <h2
      id="cafe-note-heading"
      class="text-sm font-semibold text-theme-900 dark:text-zinc-100"
    >
      寫一張便利貼
    </h2>

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

    <StickyNoteCanvas
      ref="pad"
      :paper-color="paperColor"
      @change="drew = $event"
    />

    <div>
      <p class="mb-1 text-xs text-theme-700 dark:text-zinc-400">
        點一下牆面決定貼在哪裡
      </p>
      <div
        ref="wall"
        class="relative mx-auto aspect-4/3 w-full max-w-sm cursor-pointer overflow-hidden rounded-md border-2 border-amber-900/40 bg-amber-100 dark:border-amber-700/50 dark:bg-amber-950/40"
        data-testid="cafe-note-placement"
        @click="placeAt"
      >
        <span
          class="pointer-events-none absolute size-8 -translate-x-1/2 -translate-y-1/2 rounded-sm shadow-md"
          :class="`note-paper-${paperColor}`"
          :style="{
            left: `${x * 100}%`,
            top: `${y * 100}%`,
            transform: `translate(-50%, -50%) rotate(${rotation}deg)`,
          }"
          aria-hidden="true"
        ></span>
      </div>
      <div class="mt-1 flex justify-center gap-1">
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

    <p
      v-if="errorMessage"
      role="alert"
      class="text-sm text-red-800 dark:text-red-300"
      data-testid="cafe-note-error"
    >
      {{ errorMessage }}
    </p>

    <div class="flex flex-wrap items-center gap-2">
      <button
        type="button"
        class="flex items-center gap-1 rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1.5 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 disabled:border-theme-300 disabled:bg-theme-200 disabled:text-theme-600 dark:border-theme-900 dark:bg-theme-700 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-700 dark:disabled:text-zinc-400"
        :disabled="!canSubmit"
        data-testid="cafe-note-submit"
        @click="submit"
      >
        貼上牆 <AcornIcon /> {{ price }}
      </button>
      <button
        type="button"
        class="text-sm text-theme-700 underline dark:text-zinc-400"
        @click="emit('close')"
      >
        先不貼了
      </button>
      <span
        v-if="balance < price"
        class="text-xs text-theme-700 dark:text-zinc-400"
      >
        橡實不夠，目前有 {{ balance }} 顆
      </span>
    </div>
  </section>
</template>
