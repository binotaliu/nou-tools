<script setup>
// What is on the table at a café seat, shown inside the control panel: the
// drink being made, the cup with its level, or an empty table. The three
// actions are the ones the server offers: a sip, the same drink again, put it
// away. This only disables what is obviously out of reach; the server judges.
import { computed, onMounted, onUnmounted, ref } from 'vue'
import AcornIcon from '../Acorns/AcornIcon.vue'

const props = defineProps({
  // The seat's `coffee` (CafeCoffeeViewModel) or null.
  coffee: { type: Object, default: null },
  balance: { type: Number, default: 0 },
  busy: { type: Boolean, default: false },
  errorMessage: { type: String, default: null },
  // Minutes a seat can go without an order before it is given back.
  graceMinutes: { type: Number, default: 3 },
})

const emit = defineEmits(['sip', 'reorder', 'put-away', 'open-menu'])

// Its own fast clock: the page's runs every second at best, and this one
// counts down the seconds a drink takes to arrive.
const now = ref(Date.now())
let handle = null

onMounted(() => {
  handle = setInterval(() => {
    now.value = Date.now()
  }, 500)
})

onUnmounted(() => clearInterval(handle))

const live = computed(() =>
  props.coffee && new Date(props.coffee.expiresAt).getTime() > now.value
    ? props.coffee
    : null
)

const secondsLeft = computed(() => {
  if (!live.value) {
    return 0
  }

  return Math.max(
    0,
    Math.ceil((new Date(live.value.readyAt).getTime() - now.value) / 1000)
  )
})

const making = computed(() => live.value !== null && secondsLeft.value > 0)
const empty = computed(() => live.value !== null && live.value.level === 0)
const canReorder = computed(
  () =>
    live.value !== null &&
    live.value.menuItemId !== null &&
    props.balance >= live.value.price
)
</script>

<template>
  <section
    aria-label="桌上的飲品"
    class="rounded-xl border-2 border-theme-200 bg-white/70 px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800/60"
    data-testid="cafe-coffee-tray"
  >
    <div
      v-if="!live"
      class="flex flex-wrap items-center justify-between gap-2 text-sm text-theme-700 dark:text-zinc-300"
    >
      <span data-testid="cafe-coffee-empty"
        >低消一杯飲料；入座後請在 {{ graceMinutes }} 分鐘內點餐。</span
      >
      <button
        type="button"
        class="rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 dark:border-theme-900 dark:bg-theme-700"
        data-testid="cafe-tray-order"
        @click="emit('open-menu')"
      >
        點餐
      </button>
    </div>

    <template v-else>
      <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <span
          class="text-3xl leading-none"
          :class="empty ? 'opacity-40' : ''"
          aria-hidden="true"
          >{{ live.emoji }}</span
        >
        <div class="min-w-0 flex-1">
          <p
            class="truncate text-sm font-semibold text-theme-900 dark:text-zinc-100"
          >
            {{ live.name }}
            <span
              class="ml-1 text-xs font-normal text-theme-700 dark:text-zinc-400"
              >{{ live.toGo ? '外帶' : '內用' }}</span
            >
          </p>
          <p
            v-if="making"
            role="status"
            class="text-xs text-theme-700 dark:text-zinc-400"
            data-testid="cafe-coffee-making"
          >
            製作中…
          </p>
          <p
            v-else
            class="flex items-center gap-1 text-xs text-theme-700 dark:text-zinc-400"
            data-testid="cafe-coffee-level"
          >
            <span aria-hidden="true" class="flex gap-0.5">
              <span
                v-for="index in live.sipsTotal"
                :key="index"
                class="h-1.5 w-3 rounded-full"
                :class="
                  index <= live.sipsTotal - live.sipsTaken
                    ? 'bg-amber-700 dark:bg-amber-400'
                    : 'bg-theme-200 dark:bg-zinc-700'
                "
              ></span>
            </span>
            {{
              empty
                ? '杯子空了'
                : `還剩 ${live.sipsTotal - live.sipsTaken} / ${live.sipsTotal} 口`
            }}
          </p>
        </div>
      </div>

      <div class="mt-2 flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 disabled:border-theme-300 disabled:bg-theme-200 disabled:text-theme-600 dark:border-theme-900 dark:bg-theme-700 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-700 dark:disabled:text-zinc-400"
          :disabled="busy || making || empty"
          data-testid="cafe-coffee-sip"
          @click="emit('sip')"
        >
          喝一口
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border-2 border-b-4 border-theme-300 bg-white px-3 py-1 text-sm font-semibold text-theme-900 transition active:translate-y-px active:border-b-2 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
          :disabled="busy || !canReorder"
          :aria-label="`再點一杯${live.name}，${live.price} 顆橡實`"
          data-testid="cafe-coffee-reorder"
          @click="emit('reorder', live)"
        >
          再點一杯 <AcornIcon /> {{ live.price }}
        </button>
        <button
          type="button"
          class="rounded-lg border-2 border-b-4 border-theme-300 bg-white px-3 py-1 text-sm font-medium text-theme-900 transition active:translate-y-px active:border-b-2 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
          :disabled="busy"
          data-testid="cafe-coffee-put-away"
          @click="emit('put-away')"
        >
          收走
        </button>
      </div>
    </template>

    <p
      v-if="errorMessage"
      role="alert"
      class="mt-2 text-sm text-red-800 dark:text-red-300"
      data-testid="cafe-coffee-error"
    >
      {{ errorMessage }}
    </p>
  </section>
</template>
