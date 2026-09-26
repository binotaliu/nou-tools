<script setup>
// The schedule page's backup prompt: what the link is for, and one button
// that opens BackupLinkDialog (owned by the page, which other entry points
// share). Not a share widget on purpose. See BackupLinkDialog.
import Icon from '../Icon.vue'

const emit = defineEmits(['open'])
</script>

<template>
  <div
    data-testid="schedule-backup-card-entry"
    class="flex flex-col gap-4 rounded-lg border border-theme-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700 dark:bg-zinc-900"
  >
    <div class="min-w-0">
      <h2
        class="flex items-center gap-2 text-lg font-semibold text-theme-900 dark:text-zinc-100"
      >
        <Icon name="lock-closed" class="size-5 shrink-0" />
        備份課表連結
      </h2>
      <p class="mt-1 text-sm text-theme-700 dark:text-zinc-400">
        清除瀏覽器資料或換裝置後，需使用此連結來找回課表。請將其視為密碼妥善保存，勿與他人分享。
      </p>
    </div>

    <button
      type="button"
      data-testid="schedule-backup-open"
      data-analytics-event="schedule_backup_open"
      data-analytics-feature="schedule"
      class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800"
      @click="emit('open')"
    >
      <Icon name="qr-code" class="size-4" />
      備份連結
    </button>
  </div>
</template>
