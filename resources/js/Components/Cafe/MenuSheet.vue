<script setup>
// The menu: pick a drink, choose 內用 or 外帶, pay in acorns. The server is
// the judge of the balance; this only disables what is obviously out of reach.
import { ref } from 'vue'
import AcornIcon from '../Acorns/AcornIcon.vue'

const props = defineProps({
  menu: { type: Array, required: true },
  balance: { type: Number, required: true },
  busyItemId: { type: Number, default: null },
  errorMessage: { type: String, default: null },
  // 內用 needs a café seat and is all a café seat can order; without one only
  // 外帶 can be ordered.
  seated: { type: Boolean, default: true },
})

const emit = defineEmits(['order', 'close'])

const toGo = ref(!props.seated)

function canAfford(item) {
  return props.balance >= item.price
}
</script>

<template>
  <section aria-labelledby="cafe-menu-heading" data-testid="cafe-menu">
    <div class="flex items-center justify-between gap-2">
      <h2
        id="cafe-menu-heading"
        class="text-lg font-semibold text-theme-900 dark:text-zinc-100"
      >
        菜單
      </h2>
      <span
        class="flex items-center gap-1 text-sm font-semibold text-theme-800 dark:text-amber-300"
        data-testid="cafe-menu-balance"
      >
        <AcornIcon /> {{ balance }}
      </span>
    </div>

    <fieldset class="mt-2 flex gap-3 text-sm text-theme-800 dark:text-zinc-300">
      <legend class="sr-only">用餐方式</legend>
      <label class="flex items-center gap-1">
        <input
          v-model="toGo"
          type="radio"
          :value="false"
          :disabled="!seated"
          data-testid="cafe-menu-dine-in"
        />
        內用
        <span v-if="!seated" class="text-xs text-theme-600 dark:text-zinc-400"
          >（請先選擇座位）</span
        >
      </label>
      <label class="flex items-center gap-1">
        <input
          v-model="toGo"
          type="radio"
          :value="true"
          :disabled="seated"
          data-testid="cafe-menu-to-go"
        />
        外帶
        <span v-if="seated" class="text-xs text-theme-600 dark:text-zinc-400"
          >（入座後僅限內容）</span
        >
      </label>
    </fieldset>

    <ul class="mt-2 divide-y divide-theme-100 dark:divide-zinc-700">
      <li
        v-for="item in menu"
        :key="item.id"
        class="flex items-center gap-3 py-2"
      >
        <span class="text-2xl" aria-hidden="true">{{ item.emoji }}</span>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-theme-900 dark:text-zinc-100">
            {{ item.name }}
          </p>
          <p
            v-if="item.description"
            class="truncate text-xs text-theme-700 dark:text-zinc-400"
          >
            {{ item.description }}
          </p>
        </div>
        <button
          type="button"
          class="flex shrink-0 items-center gap-1 rounded-lg border-2 border-b-4 border-theme-700 bg-theme-800 px-3 py-1.5 text-sm font-semibold text-white transition active:translate-y-px active:border-b-2 disabled:border-theme-300 disabled:bg-theme-200 disabled:text-theme-600 dark:border-theme-900 dark:bg-theme-700 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-700 dark:disabled:text-zinc-400"
          :disabled="!canAfford(item) || busyItemId !== null"
          :aria-label="`點 ${item.name}，${item.price} 顆橡實${canAfford(item) ? '' : '，橡實不夠'}`"
          :data-testid="`cafe-order-${item.id}`"
          @click="emit('order', { item, toGo })"
        >
          <AcornIcon /> {{ item.price }}
        </button>
      </li>
    </ul>

    <p
      v-if="errorMessage"
      role="alert"
      class="mt-2 text-sm text-red-800 dark:text-red-300"
      data-testid="cafe-menu-error"
    >
      {{ errorMessage }}
    </p>

    <button
      type="button"
      class="mt-2 text-sm text-theme-700 underline dark:text-zinc-400"
      @click="emit('close')"
    >
      先不點了
    </button>
  </section>
</template>
