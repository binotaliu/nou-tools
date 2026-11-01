<script setup>
// What the viewer has, what is about to expire, how today's and this week's
// caps stand, and the latest credits and spends.
import AcornIcon from './AcornIcon.vue'

defineProps({
  acorns: { type: Object, required: true },
})

function formatAmount(amount) {
  return amount > 0 ? `+${amount}` : String(amount)
}
</script>

<template>
  <div class="space-y-4" data-testid="acorn-ledger">
    <div
      class="flex items-center gap-3 rounded-xl border-2 border-b-4 border-theme-300 bg-theme-50 px-4 py-3 dark:border-zinc-600 dark:bg-zinc-800"
    >
      <AcornIcon size="size-8" class="text-theme-700 dark:text-amber-300" />
      <div>
        <p
          class="text-2xl font-bold text-theme-900 dark:text-zinc-100"
          data-testid="acorn-ledger-balance"
        >
          {{ acorns.balance }} 顆橡實
        </p>
        <p
          v-if="acorns.expiryLabel()"
          class="text-xs text-theme-700 dark:text-zinc-400"
        >
          {{ acorns.expiryLabel() }}
        </p>
      </div>
    </div>

    <ul class="space-y-1 text-sm text-theme-800 dark:text-zinc-300">
      <li>
        自習室：今天 {{ acorns.studyToday }}/{{ acorns.studyDailyCap }}，本週
        {{ acorns.studyThisWeek }}/{{ acorns.studyWeeklyCap }}
      </li>
      <li>
        學習進度：今天 {{ acorns.learningToday }}/{{ acorns.learningDailyCap }}
      </li>
    </ul>

    <p class="text-xs text-theme-700 dark:text-zinc-400">
      每專注 10 分鐘，就可獲得 1
      顆（面授課除外）。使用學習進度表也可獲得橡實。每天和每週可獲得的橡實數有上限。得到的橡實只能放
      30 天。
    </p>

    <div>
      <h3 class="mb-1 text-sm font-semibold text-theme-900 dark:text-zinc-100">
        最近紀錄
      </h3>
      <p
        v-if="!acorns.recent.length"
        class="text-sm text-theme-700 dark:text-zinc-400"
      >
        還沒有紀錄，去自習室專注一下吧。
      </p>
      <ul
        v-else
        class="max-h-64 divide-y divide-theme-100 overflow-y-auto rounded-lg border border-theme-200 dark:divide-zinc-700 dark:border-zinc-700"
        data-testid="acorn-ledger-recent"
      >
        <li
          v-for="entry in acorns.recent"
          :key="entry.id"
          class="flex items-center justify-between gap-3 px-3 py-2 text-sm"
        >
          <span class="text-theme-900 dark:text-zinc-100">
            {{ entry.label }}
            <span class="text-xs text-theme-600 dark:text-zinc-400">
              {{ entry.earnedOn }}
            </span>
          </span>
          <span
            class="font-mono font-semibold tabular-nums"
            :class="
              entry.amount > 0
                ? 'text-emerald-700 dark:text-emerald-400'
                : 'text-theme-800 dark:text-zinc-300'
            "
          >
            {{ formatAmount(entry.amount) }}
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>
