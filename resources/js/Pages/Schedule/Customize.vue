<script setup>
// Uses the `useScheduleCustomize` composable for the custom link list's
// add/remove state, and Inertia's useForm() for the real PUT submission
// (ScheduleCustomizationController::update() is a plain
// validate-then-redirect action).
import { computed, reactive, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useScheduleCustomize from '../../Composables/useScheduleCustomize'
import useAnnouncementPreferences from '../../Composables/useAnnouncementPreferences'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
  announcementPreferences: {
    type: Object,
    required: true,
  },
})

const { links, addLink, removeLink } = useScheduleCustomize({
  links: props.viewModel.customLinks,
})

const TABS = [
  { key: 'display', label: '顯示設定' },
  { key: 'announcements', label: '公告分類設定' },
]
// A redirect-back after a failed submission lands on this same page, so
// start on whichever tab actually has errors to show rather than always
// defaulting to "display".
const activeTab = ref(
  'announcement_categories' in (usePage().props.errors ?? {})
    ? 'announcements'
    : 'display'
)

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

// --- announcement category preferences ---
// The grouped/flat catalog tree and selected-source-categories are a pure
// view-layer reshaping of `announcementPreferences.sourceGroups` for the
// checkbox tree's convenience, so they're derived here rather than added
// to AnnouncementPreferencesPageData.
const groupLabels = computed(() =>
  Object.fromEntries(
    props.announcementPreferences.sourceGroups.map(g => [g.group, g.groupLabel])
  )
)

const groupedCatalogTree = computed(() =>
  Object.fromEntries(
    props.announcementPreferences.sourceGroups.map(group => [
      group.group,
      Object.fromEntries(
        group.sources.map(source => [source.source, source.availableCategories])
      ),
    ])
  )
)

const flatCatalogTree = computed(() =>
  Object.assign({}, ...Object.values(groupedCatalogTree.value))
)

const selectedSourceCategories = computed(() => {
  const selected = {}

  props.announcementPreferences.sourceGroups.forEach(group => {
    group.sources.forEach(source => {
      if (source.selectedCategories.length > 0) {
        selected[source.source] = source.selectedCategories
      }
    })
  })

  return selected
})

const {
  state: announcementState,
  isGroupChecked,
  isGroupIndeterminate,
  isGroupExpanded,
  toggleGroupExpansion,
  toggleGroup,
  isSourceChecked,
  isSourceIndeterminate,
  isSourceExpanded,
  toggleSourceExpansion,
  toggleSource,
  isCategoryChecked,
  toggleCategory,
} = useAnnouncementPreferences({
  catalog: groupedCatalogTree.value,
  flatCatalog: flatCatalogTree.value,
  selected: selectedSourceCategories.value,
})

const announcementForm = useForm({
  announcement_categories: {},
})

function submitAnnouncementPreferences() {
  announcementForm.announcement_categories = announcementState.selected

  announcementForm.put(
    `/schedules/${props.viewModel.scheduleUuid}/announcement-preferences`,
    {
      preserveScroll: true,
    }
  )
}
</script>

<template>
  <Head
    :title="`自訂課表 - ${viewModel.scheduleName || '我的課表'} - NOU 小幫手`"
  />

  <AppLayout>
    <div class="mx-auto max-w-4xl">
      <div
        class="mb-8 flex flex-col items-start justify-between gap-3 sm:flex-row"
      >
        <div>
          <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
            自訂課表
          </h2>
          <p class="mt-2 text-sm text-theme-700 dark:text-zinc-400">
            調整課表頁顯示區塊、常用連結，以及最新公告要顯示的分類。
          </p>
        </div>

        <Link
          :href="`/schedules/${viewModel.scheduleUuid}`"
          class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 sm:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
        >
          <Icon name="arrow-left" class="size-4" />
          回到課表
        </Link>
      </div>

      <div
        role="tablist"
        class="mb-6 flex gap-1 border-b border-theme-200 dark:border-zinc-700"
      >
        <button
          v-for="tab in TABS"
          :key="tab.key"
          type="button"
          role="tab"
          :aria-selected="activeTab === tab.key"
          :data-testid="`schedule-customize-tab-${tab.key}`"
          class="border-b-2 px-4 py-2 text-sm font-semibold transition"
          :class="
            activeTab === tab.key
              ? 'border-theme-600 text-theme-900 dark:border-zinc-300 dark:text-zinc-100'
              : 'border-transparent text-theme-700 hover:text-theme-800 dark:text-zinc-400 dark:hover:text-zinc-200'
          "
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </div>

      <form
        v-show="activeTab === 'display'"
        class="space-y-6"
        @submit.prevent="submit"
      >
        <div
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
              顯示區塊
            </h2>
            <div class="text-sm text-theme-700 dark:text-zinc-400">
              取消勾選即可在課表頁隱藏對應區塊。
            </div>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <label
              v-for="[key, label] in DISPLAY_OPTION_LABELS"
              :key="key"
              class="flex cursor-pointer items-center gap-3 rounded-lg border border-theme-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
            >
              <input
                v-model="displayOptions[key]"
                type="checkbox"
                :aria-label="label"
                class="size-4 rounded border-theme-400 text-theme-700 focus:ring-theme-500 dark:border-zinc-600 dark:text-zinc-300"
              />
              <span
                aria-hidden="true"
                class="text-sm font-medium text-theme-800 dark:text-zinc-200"
              >
                {{ label }}
              </span>
            </label>
          </div>
        </div>

        <div
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
              常用連結：自訂連結
            </h2>
            <div class="text-sm text-theme-700 dark:text-zinc-400">
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
              class="rounded-lg border border-theme-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"
            >
              <div class="grid gap-3 md:grid-cols-[1fr_2fr_auto] md:items-end">
                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-theme-800 dark:text-zinc-200"
                  >
                    連結名稱
                  </label>
                  <input
                    v-model="link.title"
                    type="text"
                    maxlength="50"
                    placeholder="例如：我的課程群組"
                    class="w-full rounded-lg border border-theme-300 px-3 py-2 text-sm focus:border-theme-500 focus:outline-none dark:border-zinc-600"
                  />
                </div>

                <div>
                  <label
                    class="mb-1 block text-sm font-semibold text-theme-800 dark:text-zinc-200"
                  >
                    網址
                  </label>
                  <input
                    v-model="link.url"
                    type="url"
                    maxlength="2048"
                    placeholder="https://example.com"
                    class="w-full rounded-lg border border-theme-300 px-3 py-2 text-sm focus:border-theme-500 focus:outline-none dark:border-zinc-600"
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
              class="rounded-lg border border-dashed border-theme-300 bg-theme-50 px-4 py-6 text-center text-sm text-theme-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400"
            >
              尚未新增自訂連結。
            </div>

            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-200 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 disabled:border-theme-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
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
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800 disabled:bg-theme-400 sm:w-auto"
          >
            <Icon name="check" class="size-4" />
            儲存自訂設定
          </button>

          <Link
            :href="`/schedules/${viewModel.scheduleUuid}`"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 sm:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            取消
          </Link>
        </div>
      </form>

      <form
        v-show="activeTab === 'announcements'"
        class="space-y-6"
        @submit.prevent="submitAnnouncementPreferences"
      >
        <p class="text-sm text-theme-700 dark:text-zinc-400">
          選擇要在課表頁「最新公告」區塊顯示的公告分類。未選擇任何分類時，該區塊將不顯示任何公告。
        </p>
        <div
          v-for="(groupLabel, groupValue) in groupLabels"
          :key="groupValue"
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2 class="text-xl font-semibold text-theme-900 dark:text-zinc-100">
              {{ groupLabel }}
            </h2>
          </div>

          <div class="space-y-2">
            <div
              class="flex items-center justify-between gap-2 rounded-lg border border-theme-200 bg-theme-50 px-2 dark:border-zinc-700 dark:bg-zinc-950"
            >
              <label
                class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 rounded-md px-2 py-2"
              >
                <input
                  type="checkbox"
                  :aria-label="`${groupLabel}（全選）`"
                  class="size-4 rounded border-theme-300 text-theme-700 focus:ring-theme-300 dark:border-zinc-600 dark:text-zinc-300"
                  :checked="isGroupChecked(groupValue)"
                  :indeterminate="isGroupIndeterminate(groupValue)"
                  @change="toggleGroup(groupValue, $event.target.checked)"
                />
                <span
                  aria-hidden="true"
                  class="min-w-0 truncate text-sm font-semibold text-theme-900 dark:text-zinc-100"
                >
                  {{ groupLabel }}（全選）
                </span>
              </label>

              <button
                type="button"
                class="inline-flex items-center rounded-md p-2 text-theme-700 transition hover:bg-theme-100 hover:text-theme-800 dark:text-zinc-400 dark:hover:bg-zinc-900 dark:hover:text-zinc-200"
                :aria-expanded="isGroupExpanded(groupValue)"
                :aria-label="`展開或收合 ${groupLabel} 分類`"
                @click="toggleGroupExpansion(groupValue)"
              >
                <span
                  class="inline-flex transition"
                  :class="isGroupExpanded(groupValue) ? 'rotate-180' : ''"
                >
                  <Icon name="chevron-down" class="size-4" />
                </span>
              </button>
            </div>

            <div
              v-show="isGroupExpanded(groupValue)"
              class="flex flex-wrap gap-2 pl-2"
            >
              <div
                v-for="(categories, source) in groupedCatalogTree[groupValue] ??
                {}"
                :key="source"
                class="flex flex-col gap-2"
                :class="isSourceExpanded(source) ? 'w-full' : ''"
              >
                <div
                  class="inline-flex w-fit items-center gap-1 rounded-full border py-1 pr-1 pl-3 text-sm transition"
                  :class="
                    isSourceChecked(source) || isSourceIndeterminate(source)
                      ? 'border-theme-300 bg-theme-100 font-medium text-theme-800 dark:border-theme-800 dark:bg-theme-900/60 dark:text-theme-300'
                      : 'border-theme-300 text-theme-700 hover:bg-theme-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-950'
                  "
                >
                  <label class="flex cursor-pointer items-center gap-2">
                    <input
                      type="checkbox"
                      :aria-label="source"
                      class="size-4 rounded border-theme-300 text-theme-700 focus:ring-theme-300 dark:border-zinc-600 dark:text-zinc-300"
                      :checked="isSourceChecked(source)"
                      :indeterminate="isSourceIndeterminate(source)"
                      @change="toggleSource(source, $event.target.checked)"
                    />
                    <span aria-hidden="true">{{ source }}</span>
                  </label>

                  <button
                    v-if="categories.length > 0"
                    type="button"
                    class="inline-flex items-center rounded-full p-1 text-current opacity-70 transition hover:bg-black/5 hover:opacity-100 dark:hover:bg-white/10"
                    :aria-expanded="isSourceExpanded(source)"
                    :aria-label="`展開或收合 ${source} 分類`"
                    @click="toggleSourceExpansion(source)"
                  >
                    <span
                      class="inline-flex transition"
                      :class="isSourceExpanded(source) ? 'rotate-180' : ''"
                    >
                      <Icon name="chevron-down" class="size-3.5" />
                    </span>
                  </button>
                </div>

                <div
                  v-show="isSourceExpanded(source)"
                  class="flex flex-wrap gap-2 pl-2"
                >
                  <label
                    v-for="category in categories"
                    :key="category"
                    class="cursor-pointer rounded-full border px-3 py-1 text-sm transition"
                    :class="
                      isCategoryChecked(source, category)
                        ? 'border-theme-300 bg-theme-100 font-medium text-theme-800 dark:border-theme-800 dark:bg-theme-900/60 dark:text-theme-300'
                        : 'border-theme-300 text-theme-700 hover:bg-theme-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-950'
                    "
                  >
                    <input
                      type="checkbox"
                      class="sr-only"
                      :aria-label="category"
                      :checked="isCategoryChecked(source, category)"
                      @change="
                        toggleCategory(source, category, $event.target.checked)
                      "
                    />
                    <span aria-hidden="true">{{ category }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
          <button
            type="submit"
            :disabled="announcementForm.processing"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800 disabled:bg-theme-400 sm:w-auto"
          >
            <Icon name="check" class="size-4" />
            儲存公告分類設定
          </button>

          <Link
            :href="`/schedules/${viewModel.scheduleUuid}`"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 font-semibold text-theme-900 transition hover:bg-theme-50 sm:w-auto dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            取消
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
