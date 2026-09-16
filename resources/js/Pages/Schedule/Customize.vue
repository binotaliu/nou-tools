<script setup>
// Uses the `useScheduleCustomize` composable for the custom link list's
// add/remove state, and Inertia's useForm() for the real PUT submission
// (ScheduleCustomizationController::update() is a plain
// validate-then-redirect action).
import { reactive } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useScheduleCustomize from '../../Composables/useScheduleCustomize'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const { links, addLink, removeLink } = useScheduleCustomize({
  links: props.viewModel.customLinks,
})

// Note: ScheduleDisplayOptionsViewModel is `#[MapName(SnakeCaseMapper::class)]`
// server-side, so its JSON keys are already snake_case, matching the
// `display_options.*` field names the update DTO/validation expects.
const DISPLAY_OPTION_LABELS = [
  ['show_greeting', '問候語區塊'],
  ['show_schedule_items', '課程清單'],
  ['show_common_links', '常用連結'],
  ['show_class_dates', '面授日期'],
  ['show_school_calendar', '學校行事曆'],
  ['show_exam_info', '考試資訊'],
  ['show_announcements', '最新公告'],
  ['show_share_section', '分享連結與 QRCode'],
  ['show_print_button', '列印按鈕'],
]

const displayOptions = reactive(
  Object.fromEntries(
    DISPLAY_OPTION_LABELS.map(([key]) => [
      key,
      props.viewModel.displayOptions[key],
    ])
  )
)

const form = useForm({
  display_options: {},
  custom_links: [],
})

function submit() {
  form.display_options = { ...displayOptions }
  form.custom_links = links.value

  form.put(`/schedules/${props.viewModel.scheduleUuid}/customize`, {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head
    :title="`自訂課表 - ${viewModel.scheduleName || '我的課表'} - NOU 小幫手`"
  >
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <AppLayout>
    <div class="mx-auto max-w-4xl">
      <div
        class="mb-8 flex flex-col items-start justify-between gap-3 sm:flex-row"
      >
        <div>
          <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
            自訂課表顯示
          </h2>
          <p class="mt-2 text-sm text-warm-600 dark:text-zinc-400">
            調整課表頁顯示區塊，並在「常用連結」加入你的自訂連結。
          </p>
        </div>

        <Link
          :href="`/schedules/${viewModel.scheduleUuid}`"
          class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 sm:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
        >
          <Icon name="arrow-left" class="size-4" />
          回到課表
        </Link>
      </div>

      <form class="space-y-6" @submit.prevent="submit">
        <div
          class="rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2 class="text-xl font-semibold text-warm-900 dark:text-zinc-100">
              顯示區塊
            </h2>
            <div class="text-sm text-warm-600 dark:text-zinc-400">
              取消勾選即可在課表頁隱藏對應區塊。
            </div>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <label
              v-for="[key, label] in DISPLAY_OPTION_LABELS"
              :key="key"
              class="flex cursor-pointer items-center gap-3 rounded-lg border border-warm-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
            >
              <input
                v-model="displayOptions[key]"
                type="checkbox"
                class="size-4 rounded border-warm-400 text-warm-700 focus:ring-warm-500 dark:border-zinc-600 dark:text-zinc-300"
              />
              <span
                class="text-sm font-medium text-warm-800 dark:text-zinc-200"
              >
                {{ label }}
              </span>
            </label>
          </div>

          <p class="mt-4 text-sm text-warm-600 dark:text-zinc-400">
            想調整「最新公告」要顯示哪些分類？前往
            <Link
              :href="`/schedules/${viewModel.scheduleUuid}/announcement-preferences`"
              class="font-medium text-orange-700 hover:underline dark:text-orange-400"
            >
              公告分類設定
            </Link>
            。
          </p>
        </div>

        <div
          class="rounded-lg border border-warm-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2 class="text-xl font-semibold text-warm-900 dark:text-zinc-100">
              常用連結：自訂連結
            </h2>
            <div class="text-sm text-warm-600 dark:text-zinc-400">
              最多可新增 20 筆。請輸入完整網址（以 https:// 開頭）。僅限
              *.nou.edu.tw、line.me、docs.google.com 網域。
            </div>
          </div>

          <div
            v-if="
              form.errors.custom_links ||
              Object.keys(form.errors).some(k => k.startsWith('custom_links.'))
            "
            class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          >
            <ul class="space-y-1">
              <li v-for="(error, key) in form.errors" :key="key">
                {{ error }}
              </li>
            </ul>
          </div>

          <div class="space-y-3">
            <div
              v-for="(link, index) in links"
              :key="index"
              class="rounded-lg border border-warm-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"
            >
              <div class="grid gap-3 md:grid-cols-[1fr_2fr_auto] md:items-end">
                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-warm-800 dark:text-zinc-200"
                  >
                    連結名稱
                  </label>
                  <input
                    v-model="link.title"
                    type="text"
                    maxlength="50"
                    placeholder="例如：我的課程群組"
                    class="w-full rounded-lg border border-warm-300 px-3 py-2 text-sm focus:border-warm-500 focus:outline-none dark:border-zinc-600"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-warm-800 dark:text-zinc-200"
                  >
                    網址
                  </label>
                  <input
                    v-model="link.url"
                    type="url"
                    maxlength="2048"
                    placeholder="https://example.com"
                    class="w-full rounded-lg border border-warm-300 px-3 py-2 text-sm focus:border-warm-500 focus:outline-none dark:border-zinc-600"
                  />
                </div>

                <button
                  type="button"
                  class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-100 bg-red-100 px-3 py-1 text-sm font-semibold text-red-700 transition hover:bg-red-200"
                  @click="removeLink(index)"
                >
                  移除
                </button>
              </div>
            </div>

            <div
              v-if="links.length === 0"
              class="rounded-lg border border-dashed border-warm-300 bg-warm-50 px-4 py-6 text-center text-sm text-warm-600 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400"
            >
              尚未新增自訂連結。
            </div>

            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-warm-200 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 disabled:border-warm-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              :disabled="links.length >= 20"
              @click="addLink()"
            >
              <Icon name="plus" class="size-4" />
              新增連結
            </button>
          </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-warm-600 bg-warm-600 px-4 py-2 font-semibold text-white transition hover:bg-warm-700 disabled:bg-warm-400 sm:w-auto"
          >
            <Icon name="check" class="size-4" />
            儲存自訂設定
          </button>

          <Link
            :href="`/schedules/${viewModel.scheduleUuid}`"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-warm-500 bg-white px-4 py-2 font-semibold text-warm-900 transition hover:bg-warm-50 sm:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            取消
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
