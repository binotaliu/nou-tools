<script setup>
// Appearance (theme, accent, font size) is stored per device in localStorage by the composables, so
// the server sends nothing for it. The installed phone PWA hides the header's
// theme popover, so this page (linked from the bottom bar's 更多 sheet) is
// where it lives there.
//
// The notification switches are the remembered schedule's two push opt-ins,
// which are per schedule on the server. They share this browser's single push
// subscription, so neither switch unsubscribes it: turning one off only
// clears its own flag (see usePushSubscription).
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import ThemeSettings from '../../Components/ThemeSettings.vue'
import usePushSubscription from '../../Composables/usePushSubscription'

const props = defineProps({
  // null when no schedule is remembered on this device.
  notifications: {
    type: Object,
    default: null,
  },
  vapidPublicKey: {
    type: String,
    default: null,
  },
})

const permissionError = ref('')

// Class-start reminders: the schedule's own subscribe/unsubscribe endpoints
// set and clear `notify_on_class_start`.
const classUrl = props.notifications
  ? `/schedules/${props.notifications.scheduleToken}/push-subscriptions`
  : null
const classReminders = usePushSubscription({
  vapidPublicKey: props.vapidPublicKey,
  subscribeUrl: classUrl,
  unsubscribeUrl: classUrl,
  enabled: props.notifications?.classReminders,
})

// Timer-end notification: the study room's endpoint only registers the
// browser, so the opt-in is saved separately once that has succeeded.
const timerEnd = usePushSubscription({
  vapidPublicKey: props.vapidPublicKey,
  subscribeUrl: '/study-room/push-subscriptions',
  enabled: props.notifications?.timerEnd,
})
const timerEndSaving = ref(false)

async function saveTimerEnd(value) {
  timerEndSaving.value = true

  try {
    await window.axios.put('/study-room/timer-end-notification', {
      enabled: value,
    })
    timerEnd.enabled.value = value
  } catch (error) {
    timerEnd.enabled.value = !value
    permissionError.value =
      error.response?.data?.message ?? '儲存失敗，請稍後再試。'
  } finally {
    timerEndSaving.value = false
  }
}

async function toggleTimerEnd() {
  permissionError.value = ''

  if (timerEnd.enabled.value) {
    await saveTimerEnd(false)

    return
  }

  if (!(await timerEnd.enable())) {
    permissionError.value = '沒有取得通知權限，請在瀏覽器設定中允許本站通知。'

    return
  }

  await saveTimerEnd(true)
}

async function toggleClassReminders() {
  permissionError.value = ''

  if (classReminders.enabled.value) {
    await classReminders.disable()

    return
  }

  if (!(await classReminders.enable())) {
    permissionError.value = '沒有取得通知權限，請在瀏覽器設定中允許本站通知。'
  }
}

const supported = computed(() => classReminders.supported.value)

const rows = computed(() => [
  {
    key: 'class-reminders',
    label: '面授開始前通知',
    description: '面授開始前 10 分鐘接收推播通知。',
    enabled: classReminders.enabled.value,
    disabled: classReminders.busy.value,
    toggle: toggleClassReminders,
  },
  {
    key: 'timer-end',
    label: '自習室時間到通知',
    description: props.notifications?.hasStudyRoomProfile
      ? '計時器時間到時接收推播通知。'
      : '要先到自習室設定暱稱才能開啟。',
    enabled: timerEnd.enabled.value,
    disabled:
      timerEnd.busy.value ||
      timerEndSaving.value ||
      !props.notifications?.hasStudyRoomProfile,
    toggle: toggleTimerEnd,
  },
])
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
        <p class="mt-1 mb-4 text-sm text-theme-700 dark:text-zinc-400">
          設定只會套用到這台裝置中。
        </p>

        <ThemeSettings large />
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="settings-schedule"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          課表
        </h3>

        <p
          v-if="!notifications"
          class="mt-1 text-sm text-theme-700 dark:text-zinc-400"
          data-testid="settings-schedule-no-schedule"
        >
          請先
          <Link
            href="/schedules/my"
            class="font-medium underline underline-offset-2"
          >
            建立或找回你的課表
          </Link>
          。
        </p>

        <div v-else class="mt-3 flex flex-col gap-2 sm:flex-row">
          <Link
            :href="`/schedules/${notifications.scheduleToken}/edit`"
            data-testid="settings-schedule-edit"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            編輯課表
          </Link>
          <Link
            :href="`/schedules/${notifications.scheduleToken}/customize`"
            data-testid="settings-schedule-customize"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            自訂課表頁顯示
          </Link>
        </div>
      </section>

      <section
        class="rounded-xl border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="settings-notifications"
      >
        <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-100">
          通知
        </h3>

        <p
          v-if="!notifications"
          class="mt-1 text-sm text-theme-700 dark:text-zinc-400"
          data-testid="settings-notifications-no-schedule"
        >
          通知是綁在課表上的，請先
          <Link
            href="/schedules/my"
            class="font-medium underline underline-offset-2"
          >
            建立或找回你的課表
          </Link>
          。
        </p>

        <template v-else>
          <p
            v-if="!supported"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
            data-testid="settings-notifications-unsupported"
          >
            這個瀏覽器不支援網頁通知。iPhone 需先將本站加入主畫面。
          </p>

          <ul
            class="mt-2 divide-y divide-theme-100 dark:divide-zinc-800"
            :class="{ 'pointer-events-none opacity-50': !supported }"
          >
            <li
              v-for="row in rows"
              :key="row.key"
              class="flex items-center justify-between gap-4 py-3"
            >
              <div class="min-w-0">
                <p
                  :id="`settings-notify-${row.key}-label`"
                  class="text-sm font-medium text-theme-800 dark:text-zinc-200"
                >
                  {{ row.label }}
                </p>
                <p class="text-xs text-theme-700 dark:text-zinc-400">
                  {{ row.description }}
                </p>
              </div>
              <button
                type="button"
                role="switch"
                :aria-checked="row.enabled"
                :aria-labelledby="`settings-notify-${row.key}-label`"
                :disabled="row.disabled || !supported"
                :class="
                  row.enabled
                    ? 'bg-theme-700 dark:bg-zinc-300'
                    : 'bg-theme-200 dark:bg-zinc-700'
                "
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                :data-testid="`settings-notify-${row.key}`"
                @click="row.toggle()"
              >
                <span
                  :class="row.enabled ? 'translate-x-6' : 'translate-x-1'"
                  class="inline-block size-4 transform rounded-full bg-white shadow transition-transform dark:bg-zinc-900"
                ></span>
              </button>
            </li>
          </ul>

          <p
            v-if="permissionError"
            class="mt-2 text-sm text-red-600 dark:text-red-400"
            data-testid="settings-notifications-error"
          >
            {{ permissionError }}
          </p>
        </template>
      </section>
    </div>
  </AppLayout>
</template>
