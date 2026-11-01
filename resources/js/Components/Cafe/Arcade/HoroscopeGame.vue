<script setup>
// 星座運勢: pick a sign, put a coin in, get today's reading. The reading is
// calculated on the server (the same sign gets the same reading all Taipei
// day), so asking again for a sign that is already unlocked is free and does
// not even make a request. The server is the judge of the balance; this only
// disables what is obviously out of reach.
import { computed, nextTick, onMounted, ref } from 'vue'
import AcornIcon from '../../Acorns/AcornIcon.vue'

const props = defineProps({
  canPlay: { type: Boolean, required: true },
  hasSchedule: { type: Boolean, required: true },
  hasNickname: { type: Boolean, required: true },
  balance: { type: Number, required: true },
})

const emit = defineEmits(['spent'])

const SIGN_KEY = 'nou:cafe:arcade:horoscope-sign:v1'
const DRAW_MS = 900

const machine = ref(null)
const loadError = ref(null)
const selected = ref(null)
const phase = ref('pick')
const reading = ref(null)
const error = ref(null)
const resultHeading = ref(null)

const unlockedBySign = computed(() => {
  const map = {}

  for (const item of machine.value?.unlocked ?? []) {
    map[item.sign] = item
  }

  return map
})

const selectedUnlocked = computed(
  () => selected.value !== null && selected.value in unlockedBySign.value
)

const canAfford = computed(
  () => machine.value !== null && props.balance >= machine.value.price
)

const hint = computed(() => {
  if (selectedUnlocked.value || selected.value === null) {
    return null
  }

  if (!props.hasSchedule) {
    return '建立課表後才能投幣。'
  }

  if (!props.hasNickname) {
    return '先到自習室設定暱稱後才能投幣。'
  }

  if (!canAfford.value) {
    return '橡實不夠囉。'
  }

  return null
})

const actionDisabled = computed(
  () =>
    machine.value === null ||
    selected.value === null ||
    (!selectedUnlocked.value && (!props.canPlay || !canAfford.value))
)

function remember() {
  try {
    localStorage.setItem(SIGN_KEY, selected.value)
  } catch {
    // Remembering the pick is a convenience only.
  }
}

function readRemembered() {
  try {
    return localStorage.getItem(SIGN_KEY)
  } catch {
    return null
  }
}

function showDate(date) {
  const [, month, day] = date.split('-').map(Number)

  return `${month}/${day}`
}

async function showResult(item) {
  reading.value = item
  phase.value = 'result'
  await nextTick()
  resultHeading.value?.focus()
}

async function insertCoin() {
  error.value = null

  if (selectedUnlocked.value) {
    await showResult(unlockedBySign.value[selected.value])

    return
  }

  phase.value = 'drawing'

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const wait = new Promise(resolve =>
    setTimeout(resolve, reduced ? 0 : DRAW_MS)
  )

  try {
    const [response] = await Promise.all([
      window.axios.post('/cafe/arcade/horoscope', { sign: selected.value }),
      wait,
    ])

    machine.value.unlocked = [
      ...machine.value.unlocked.filter(
        item => item.sign !== response.data.reading.sign
      ),
      response.data.reading,
    ]
    emit('spent', response.data.acorns)
    await showResult(response.data.reading)
  } catch (failure) {
    phase.value = 'pick'
    error.value =
      failure.response?.data?.message ?? '網路好像不太穩定，請稍後再試一次。'
  }
}

function pickAnother() {
  phase.value = 'pick'
  error.value = null
}

onMounted(async () => {
  try {
    machine.value = (
      await window.axios.get('/cafe/arcade/horoscope')
    ).data.machine
  } catch {
    loadError.value = '機台暫時無法使用，請稍後再試。'

    return
  }

  const remembered = readRemembered()

  if (machine.value.signs.some(sign => sign.value === remembered)) {
    selected.value = remembered
  }
})
</script>

<template>
  <section aria-labelledby="horoscope-heading" data-testid="horoscope-game">
    <h2 id="horoscope-heading" class="text-base font-semibold text-amber-100">
      星座運勢
    </h2>

    <p
      v-if="loadError"
      role="alert"
      class="mt-2 text-sm text-red-300"
      data-testid="horoscope-load-error"
    >
      {{ loadError }}
    </p>

    <p v-else-if="machine === null" class="mt-3 text-sm text-amber-100/70">
      機台啟動中…
    </p>

    <!-- drawing -->
    <div
      v-else-if="phase === 'drawing'"
      role="status"
      class="flex flex-col items-center gap-3 py-10 text-sm text-amber-100"
      data-testid="horoscope-drawing"
    >
      <span
        class="flex size-12 animate-spin items-center justify-center rounded-full border-4 border-[#c9974f] bg-[#f2d98b] motion-reduce:animate-none"
      >
        <AcornIcon size="size-6" />
      </span>
      命運轉動中…
    </div>

    <!-- result -->
    <div
      v-else-if="phase === 'result' && reading"
      class="mt-2 space-y-3"
      data-testid="horoscope-result"
    >
      <h3
        ref="resultHeading"
        tabindex="-1"
        class="text-lg font-bold text-amber-50 focus:outline-none"
        data-testid="horoscope-result-heading"
      >
        <span aria-hidden="true">{{ reading.symbol }}</span>
        {{ reading.signLabel }}・{{ showDate(reading.date) }} 今日運勢
      </h3>

      <ul class="space-y-1">
        <li
          v-for="score in reading.scores"
          :key="score.key"
          class="flex items-center justify-between gap-2 text-sm"
          :data-testid="`horoscope-score-${score.key}`"
        >
          <span class="text-amber-100">{{ score.label }}</span>
          <span class="sr-only">{{ score.stars }} 顆星（滿分 5 顆）</span>
          <span class="tracking-wider text-amber-300" aria-hidden="true">
            <span
              v-for="n in 5"
              :key="n"
              :class="{ 'opacity-30': n > score.stars }"
              >★</span
            >
          </span>
        </li>
      </ul>

      <dl
        class="grid grid-cols-2 gap-x-3 gap-y-2 rounded-lg bg-white/5 p-3 text-sm"
      >
        <div>
          <dt class="text-xs text-amber-100/70">幸運數字</dt>
          <dd class="font-semibold text-amber-50">{{ reading.luckyNumber }}</dd>
        </div>
        <div>
          <dt class="text-xs text-amber-100/70">幸運色</dt>
          <dd class="flex items-center gap-1.5 font-semibold text-amber-50">
            <span
              class="size-3.5 rounded-full border border-white/40"
              :style="{ backgroundColor: reading.luckyColor.hex }"
              aria-hidden="true"
            ></span>
            {{ reading.luckyColor.name }}
          </dd>
        </div>
        <div>
          <dt class="text-xs text-amber-100/70">幸運小物</dt>
          <dd class="font-semibold text-amber-50">{{ reading.luckyItem }}</dd>
        </div>
        <div>
          <dt class="text-xs text-amber-100/70">幸運方位</dt>
          <dd class="font-semibold text-amber-50">
            {{ reading.luckyDirection }}
          </dd>
        </div>
        <div class="col-span-2">
          <dt class="text-xs text-amber-100/70">速配星座</dt>
          <dd class="font-semibold text-amber-50">
            <span aria-hidden="true">{{ reading.matchSign.symbol }}</span>
            {{ reading.matchSign.label }}
          </dd>
        </div>
      </dl>

      <ul class="list-disc space-y-1 pl-5 text-sm text-amber-50">
        <li v-for="line in reading.advice" :key="line">{{ line }}</li>
      </ul>

      <p class="text-xs text-amber-100/60">
        太陽在{{ reading.sunSignLabel }}・月亮在{{
          reading.moonSignLabel
        }}・今日守護星：{{ reading.rulerLabel }}
      </p>

      <button
        type="button"
        class="rounded-lg border-2 border-b-4 border-amber-100/30 bg-white/10 px-3 py-1.5 text-sm font-semibold text-amber-50 transition active:translate-y-px active:border-b-2"
        data-testid="horoscope-again"
        @click="pickAnother"
      >
        換個星座
      </button>
    </div>

    <!-- pick -->
    <div v-else class="mt-2 space-y-3">
      <p class="text-sm text-amber-100/80">
        選一個星座，投幣後可以看到今天的運勢。同一個星座今天再看不用再付。
      </p>

      <fieldset>
        <legend class="sr-only">選擇星座</legend>
        <div class="grid grid-cols-3 gap-1.5 sm:grid-cols-4">
          <label
            v-for="sign in machine.signs"
            :key="sign.value"
            class="relative cursor-pointer"
            :data-testid="`horoscope-sign-${sign.value}`"
          >
            <input
              v-model="selected"
              type="radio"
              name="horoscope-sign"
              :value="sign.value"
              class="peer sr-only"
              @change="remember"
            />
            <span
              class="flex flex-col items-center rounded-lg border-2 border-white/15 bg-white/5 px-1 py-1.5 text-amber-50 transition peer-checked:border-amber-300 peer-checked:bg-amber-300/20 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-amber-300"
            >
              <span class="text-xl leading-none" aria-hidden="true">{{
                sign.symbol
              }}</span>
              <span class="mt-0.5 text-xs font-semibold">{{ sign.label }}</span>
              <span class="text-[0.625rem] text-amber-100/60">{{
                sign.dateRange
              }}</span>
            </span>
            <span
              v-if="sign.value in unlockedBySign"
              class="pointer-events-none absolute -top-1.5 -right-1 rounded-full bg-emerald-600 px-1.5 text-[0.625rem] font-semibold text-white"
              >今日已解鎖</span
            >
          </label>
        </div>
      </fieldset>

      <p
        v-if="error"
        role="alert"
        class="text-sm text-red-300"
        data-testid="horoscope-error"
      >
        {{ error }}
      </p>
      <p
        v-else-if="hint"
        class="text-sm text-amber-200"
        data-testid="horoscope-hint"
      >
        {{ hint }}
      </p>

      <button
        type="button"
        class="flex items-center gap-1.5 rounded-lg border-2 border-b-4 border-amber-700 bg-amber-400 px-4 py-2 text-sm font-semibold text-zinc-900 transition active:translate-y-px active:border-b-2 disabled:border-zinc-600 disabled:bg-zinc-700 disabled:text-zinc-400"
        :disabled="actionDisabled"
        data-testid="horoscope-insert-coin"
        @click="insertCoin"
      >
        <template v-if="selectedUnlocked">檢視今日運勢</template>
        <template v-else>
          <AcornIcon /> 投入 {{ machine.price }} 顆橡實
        </template>
      </button>
    </div>
  </section>
</template>
