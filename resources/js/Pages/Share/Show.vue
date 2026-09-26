<script setup>
// Static copy plus the shared 分享 button; `url` is the site's home URL
// (ShareController), so the shared link is canonical rather than location.href.
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import ShareButton from '../../Components/ShareButton.vue'

defineProps({
  url: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <Head title="分享 NOU 小幫手 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-8">
      <div>
        <h2
          class="text-3xl font-bold tracking-tight text-theme-700 dark:text-zinc-200"
          data-testid="share-title"
        >
          分享 NOU 小幫手
        </h2>
        <p
          class="mt-3 text-theme-700 dark:text-zinc-400"
          data-testid="share-subtitle"
        >
          喜歡 NOU 小幫手嗎？歡迎分享給其他同學！
        </p>
      </div>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <ShareButton
          title="NOU 小幫手"
          :url="url"
          dialog-title="分享 NOU 小幫手"
          input-label="NOU 小幫手網址"
          test-id-prefix="share"
        />
      </section>
    </div>
  </AppLayout>
</template>
