<script setup>
// The calendar settings form uses Inertia's useForm() for the real PUT
// submission (ScheduleCalendarSettingsUpdateController is a plain
// validate-then-redirect action).
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const REMINDER_OPTIONS = [
  [5, '課前 5 分鐘'],
  [10, '課前 10 分鐘'],
  [15, '課前 15 分鐘'],
  [30, '課前 30 分鐘'],
  [60, '課前 1 小時'],
  [120, '課前 2 小時'],
  [180, '課前 3 小時'],
  [1440, '課前 1 天'],
]

// Note: ScheduleCalendarSettingsViewModel is `#[MapName(SnakeCaseMapper::class)]`
// server-side, so its JSON keys (and these prop values) are snake_case.
const form = useForm({
  include_school_calendar:
    props.viewModel.calendarSettings.include_school_calendar,
  include_exams: props.viewModel.calendarSettings.include_exams,
  class_reminders_enabled:
    props.viewModel.calendarSettings.class_reminders_enabled,
  reminder_offsets: [
    props.viewModel.calendarSettings.reminder_offsets[0] ?? 30,
    props.viewModel.calendarSettings.reminder_offsets[1] ?? '',
  ],
})

function submit() {
  form.put(`/schedules/${props.viewModel.uuid}/calendar-settings`, {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head :title="`訂閱行事曆 - ${viewModel.name || '我的課表'} - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-2xl">
      <div
        class="mb-8 flex flex-col items-start justify-between gap-3 sm:flex-row"
      >
        <div>
          <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
            訂閱行事曆
          </h2>
          <p class="mt-2 text-sm text-theme-700 dark:text-zinc-400">
            將你的課表訂閱到行事曆應用程式，以自動同步課表更新與接收提醒。
          </p>
        </div>

        <Link
          :href="`/schedules/${viewModel.uuid}`"
          class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 sm:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
        >
          <Icon name="arrow-left" class="size-4" />
          回到課表
        </Link>
        <a
          href="/manual/calendar-subscription"
          target="_blank"
          class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 sm:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
        >
          <Icon name="question-mark-circle" class="size-4" />
          說明
        </a>
      </div>

      <div class="space-y-6">
        <!-- Subscription Methods -->
        <div
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
              選擇訂閱方式
            </h2>
            <div class="text-sm text-theme-700 dark:text-zinc-400">
              選擇您常用的行事曆應用程式，點擊按鈕訂閱此課表。
            </div>
          </div>

          <div class="grid gap-3">
            <a
              :href="viewModel.calendarUrls.webcal"
              data-analytics-event="calendar_subscribe"
              data-analytics-feature="schedule"
              data-analytics-label="webcal"
              class="flex w-full items-center justify-center gap-2 rounded-lg border border-theme-200 px-4 py-3 text-center text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
            >
              Apple 日曆 (iOS / macOS)
            </a>

            <a
              :href="viewModel.calendarUrls.google"
              target="_blank"
              rel="noopener"
              data-analytics-event="calendar_subscribe"
              data-analytics-feature="schedule"
              data-analytics-label="google"
              class="flex w-full items-center justify-center gap-2 rounded-lg border border-theme-200 px-4 py-3 text-center text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
            >
              Google 日曆
            </a>

            <a
              :href="viewModel.calendarUrls.outlook"
              target="_blank"
              rel="noopener"
              data-analytics-event="calendar_subscribe"
              data-analytics-feature="schedule"
              data-analytics-label="outlook"
              class="flex w-full items-center justify-center gap-2 rounded-lg border border-theme-200 px-4 py-3 text-center text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
            >
              Windows 日曆 (Microsoft 365 / Outlook.com)
            </a>

            <a
              :href="viewModel.calendarUrls.webcal"
              data-analytics-event="calendar_subscribe"
              data-analytics-feature="schedule"
              data-analytics-label="webcal_generic"
              class="flex w-full items-center justify-center gap-2 rounded-lg border border-theme-200 px-4 py-3 text-center text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
            >
              Webcal 連結 (其他支援 Webcal 的行事曆)
            </a>

            <a
              :href="viewModel.calendarUrls.ics"
              target="_blank"
              rel="noopener"
              download
              data-analytics-event="calendar_download"
              data-analytics-feature="schedule"
              data-analytics-label="ics"
              class="flex w-full items-center justify-center gap-2 rounded-lg border border-theme-200 px-4 py-3 text-center text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
            >
              下載 iCal（.ics）
            </a>
          </div>
        </div>

        <!-- Calendar Settings -->
        <form class="space-y-6" @submit.prevent="submit">
          <div
            class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div class="mb-4">
              <h2
                class="text-xl font-semibold text-theme-900 dark:text-zinc-100"
              >
                訂閱設定
              </h2>
              <div class="text-sm text-theme-700 dark:text-zinc-400">
                保存設定後，已訂閱的行事曆會在同步時自動更新。
              </div>
            </div>

            <div class="space-y-4">
              <p class="text-sm text-theme-700 dark:text-zinc-300">
                修改設定後可能需要數小時才會更新訂閱內容。
              </p>

              <label
                class="flex cursor-pointer items-center gap-3 rounded-lg border border-theme-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
              >
                <input
                  v-model="form.include_school_calendar"
                  type="checkbox"
                  class="size-4 rounded border-theme-400 text-theme-700 focus:ring-theme-500 dark:border-zinc-600 dark:text-zinc-300"
                />
                <span
                  class="text-sm font-medium text-theme-800 dark:text-zinc-200"
                >
                  包含學校行事曆
                </span>
              </label>

              <label
                class="flex cursor-pointer items-center gap-3 rounded-lg border border-theme-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
              >
                <input
                  v-model="form.include_exams"
                  type="checkbox"
                  class="size-4 rounded border-theme-400 text-theme-700 focus:ring-theme-500 dark:border-zinc-600 dark:text-zinc-300"
                />
                <span
                  class="text-sm font-medium text-theme-800 dark:text-zinc-200"
                >
                  包含考試時段
                </span>
              </label>

              <div
                class="rounded-lg border border-theme-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"
              >
                <label class="flex cursor-pointer items-center gap-3">
                  <input
                    v-model="form.class_reminders_enabled"
                    type="checkbox"
                    class="size-4 rounded border-theme-400 text-theme-700 focus:ring-theme-500 dark:border-zinc-600 dark:text-zinc-300"
                  />
                  <span
                    class="text-sm font-medium text-theme-800 dark:text-zinc-200"
                  >
                    面授課程提醒
                  </span>
                </label>

                <span
                  class="mt-1 block text-sm text-theme-700 dark:text-zinc-400"
                >
                  註：此設定僅支援 Apple 日曆與其他相容的行事曆應用程式，
                  <strong>Google 日曆需要手動設定提醒</strong>。
                </span>

                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                  <div>
                    <label
                      class="mb-1 block text-xs font-semibold text-theme-700 dark:text-zinc-300"
                    >
                      第一次提醒
                    </label>

                    <select
                      v-model="form.reminder_offsets[0]"
                      class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                      <option
                        v-for="[value, label] in REMINDER_OPTIONS"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label
                      class="mb-1 block text-xs font-semibold text-theme-700 dark:text-zinc-300"
                    >
                      第二次提醒（可留空）
                    </label>

                    <select
                      v-model="form.reminder_offsets[1]"
                      class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                      <option value="">不設定第二次提醒</option>
                      <option
                        v-for="[value, label] in REMINDER_OPTIONS"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <p
                v-if="
                  form.errors['reminder_offsets.0'] ||
                  form.errors['reminder_offsets.1']
                "
                class="text-sm text-red-700"
              >
                {{
                  form.errors['reminder_offsets.0'] ||
                  form.errors['reminder_offsets.1']
                }}
              </p>
            </div>
          </div>

          <div class="flex flex-col gap-2 sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800 disabled:bg-theme-400 sm:w-auto"
            >
              儲存設定
            </button>

            <Link
              :href="`/schedules/${viewModel.uuid}`"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-200 bg-theme-200 px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-300 sm:w-auto dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600"
            >
              回到課表
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
