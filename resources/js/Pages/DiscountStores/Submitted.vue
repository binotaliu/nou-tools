<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'

defineProps({
  storeName: {
    type: String,
    default: null,
  },
})
</script>

<template>
  <Head title="送出成功 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-2xl">
      <div
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="flex flex-col items-center gap-4 py-8 text-center">
          <Icon name="check-circle" class="size-16 text-green-500" />

          <h2 class="text-2xl font-bold text-theme-900 dark:text-zinc-100">
            已收到您送出的資料
          </h2>

          <p class="max-w-md text-sm text-theme-600 dark:text-zinc-400">
            <template v-if="storeName">
              感謝您提供的「{{ storeName }}」優惠店家資訊！
            </template>
            <template v-else>感謝您提供的優惠店家資訊！</template>
            我們會盡快確認。確認完後即會顯示在店家列表中，請耐心等候。
          </p>

          <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
            <Link
              href="/discount-stores"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-orange-500 bg-orange-500 px-4 py-2 font-semibold text-white transition hover:bg-orange-600"
            >
              回到優惠店家列表
            </Link>
            <Link
              href="/discount-stores/create"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-orange-500 bg-orange-500 px-4 py-2 font-semibold text-white transition hover:bg-orange-600"
            >
              繼續送出其他店家
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
