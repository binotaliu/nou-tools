<script setup>
// Purely static content (no ViewModel/props): preferences are stored per
// device in localStorage by the theme composables, so the server has nothing
// to send. The installed phone PWA hides the header's theme popover, so this
// page (linked from the bottom bar's 更多 sheet) is where it lives there.
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import ThemeSettings from '../../Components/ThemeSettings.vue'
</script>

<template>
  <Head title="設定 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-8">
      <h2
        class="text-3xl font-bold tracking-tight text-theme-700 dark:text-zinc-200"
        data-testid="settings-title"
      >
        設定
      </h2>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="settings-appearance"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          外觀
        </h3>
        <p class="mt-1 mb-4 text-sm text-theme-600 dark:text-zinc-400">
          設定只會套用到這台裝置中。
        </p>

        <ThemeSettings large />
      </section>
    </div>
  </AppLayout>
</template>
