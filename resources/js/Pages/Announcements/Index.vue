<script setup>
// The source/category filter panel uses the `useAnnouncementFilter`
// composable; the mobile filter-panel toggle is a one-off local ref since
// it's not shared with any other page.
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useAnnouncementFilter from '../../Composables/useAnnouncementFilter'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const sourceCategoryTree = computed(() => {
  const tree = {}

  props.viewModel.sourceCategorySelections.forEach(selection => {
    tree[selection.source] = selection.availableCategories
  })

  return tree
})

const initialSelectedSourceCategories = computed(() => {
  const selected = {}

  props.viewModel.sourceCategorySelections.forEach(selection => {
    if (selection.selectedCategories.length > 0) {
      selected[selection.source] = selection.selectedCategories
    }
  })

  return selected
})

const {
  state,
  isCategoryChecked,
  isSourceChecked,
  isSourceIndeterminate,
  isSourceExpanded,
  toggleSourceExpansion,
  toggleSource,
  toggleCategory,
} = useAnnouncementFilter({
  sourceCategories: sourceCategoryTree.value,
  selected: initialSelectedSourceCategories.value,
})

// Sources with all their available categories selected are displayed bare
// (no per-category chips), mirroring $displaySelectedSourceCategories.
const displaySelectedSourceCategories = computed(() => {
  const display = {}

  Object.entries(state.selected).forEach(([source, selectedCategories]) => {
    const availableCategories = sourceCategoryTree.value[source] ?? []
    const hasSelectedAll =
      availableCategories.length > 0 &&
      selectedCategories.length === availableCategories.length &&
      availableCategories.every(category =>
        selectedCategories.includes(category)
      )

    display[source] = hasSelectedAll ? [] : selectedCategories
  })

  return display
})

const totalSelectedCategories = computed(() =>
  Object.values(state.selected).reduce(
    (sum, categories) => sum + categories.length,
    0
  )
)

const isFilterPanelOpen = ref(false)

function formatDate(value) {
  if (!value) {
    return '未提供'
  }

  const date = new Date(value)

  return `${date.getFullYear()}/${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}`
}

function isExpired(announcement) {
  return (
    !!announcement.expired_at && new Date(announcement.expired_at) < new Date()
  )
}
</script>

<template>
  <Head title="學校公告 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-6xl space-y-6">
      <div
        class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
      >
        <div class="space-y-2">
          <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
            學校公告
          </h2>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-200 bg-white px-4 py-2 text-sm font-medium text-theme-800 shadow-sm transition hover:border-theme-300 hover:bg-theme-50 lg:hidden dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-950"
          :aria-expanded="isFilterPanelOpen"
          aria-controls="announcement-filter-panel"
          @click="isFilterPanelOpen = !isFilterPanelOpen"
        >
          <Icon name="funnel" class="size-4" />
          <span v-show="!isFilterPanelOpen">自訂要顯示的公告來源</span>
          <span v-show="isFilterPanelOpen">隱藏篩選</span>
        </button>

        <aside
          id="announcement-filter-panel"
          class="space-y-4 lg:col-span-4 xl:col-span-3"
          :class="isFilterPanelOpen ? 'block' : 'hidden lg:block'"
        >
          <div
            class="rounded-lg border border-theme-200 bg-white p-6 lg:sticky lg:top-6 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div class="mb-4">
              <h2
                class="mb-1 text-xl font-semibold text-theme-900 dark:text-zinc-100"
              >
                選擇來源
              </h2>
            </div>

            <div class="space-y-4 overflow-x-hidden">
              <form method="GET" action="/announcements" class="space-y-4">
                <button
                  type="button"
                  class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 transition hover:border-theme-300 hover:bg-theme-50 lg:hidden dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-950"
                  @click="isFilterPanelOpen = false"
                >
                  <Icon name="eye-slash" class="size-4" />
                  收合篩選區塊
                </button>

                <div
                  class="max-h-[60vh] space-y-2 overflow-y-auto overscroll-contain rounded-xl border border-theme-200 p-2 dark:border-zinc-700"
                >
                  <section
                    v-for="(categories, source) in sourceCategoryTree"
                    :key="source"
                    class="overflow-hidden rounded-lg border border-theme-200 p-1 dark:border-zinc-700"
                  >
                    <div class="flex items-center justify-between gap-2">
                      <label
                        class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 rounded-md px-2 py-2 transition hover:bg-theme-50 dark:hover:bg-zinc-950"
                      >
                        <input
                          type="checkbox"
                          class="size-4 rounded border-theme-300 text-theme-700 focus:ring-theme-300 dark:border-zinc-600 dark:text-zinc-300"
                          :checked="isSourceChecked(source)"
                          :indeterminate="isSourceIndeterminate(source)"
                          :aria-label="source"
                          @change="toggleSource(source, $event.target.checked)"
                        />
                        <span
                          aria-hidden="true"
                          class="min-w-0 truncate text-sm font-semibold text-theme-900 dark:text-zinc-100"
                        >
                          {{ source }}
                        </span>
                      </label>

                      <button
                        v-if="categories.length > 0"
                        type="button"
                        class="inline-flex items-center rounded-md p-2 text-theme-700 transition hover:bg-theme-100 hover:text-theme-800 dark:text-zinc-400 dark:hover:bg-zinc-900 dark:hover:text-zinc-200"
                        :aria-expanded="isSourceExpanded(source)"
                        :aria-label="'展開或收合 ' + source + ' 分類'"
                        @click="toggleSourceExpansion(source)"
                      >
                        <span
                          class="inline-flex transition"
                          :class="isSourceExpanded(source) ? 'rotate-180' : ''"
                        >
                          <Icon name="chevron-down" class="size-4" />
                        </span>
                      </button>
                    </div>

                    <div
                      v-show="isSourceExpanded(source)"
                      class="mt-2 grid gap-2 pr-2 pl-9"
                    >
                      <label
                        v-for="category in categories"
                        :key="category"
                        class="flex min-w-0 cursor-pointer items-start gap-2 rounded-md px-2 py-1.5 text-sm text-theme-700 transition hover:bg-theme-50 dark:text-zinc-300 dark:hover:bg-zinc-950"
                      >
                        <input
                          type="checkbox"
                          :name="`source_categories[${source}][]`"
                          :value="category"
                          class="size-4 rounded border-theme-300 text-theme-700 focus:ring-theme-300 dark:border-zinc-600"
                          :checked="isCategoryChecked(source, category)"
                          :aria-label="category"
                          @change="
                            toggleCategory(
                              source,
                              category,
                              $event.target.checked
                            )
                          "
                        />
                        <span aria-hidden="true" class="wrap-break-word">{{
                          category
                        }}</span>
                      </label>
                    </div>
                  </section>
                </div>

                <div
                  class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-1"
                >
                  <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-theme-800 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-theme-900 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
                  >
                    <Icon name="funnel" class="size-4" />
                    套用篩選
                  </button>

                  <Link
                    href="/announcements"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-theme-200 px-4 py-2 text-sm font-medium text-theme-700 transition-colors hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                  >
                    清除條件
                  </Link>

                  <button
                    type="button"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 transition hover:border-theme-300 hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-950"
                    @click="state.selected = {}"
                  >
                    <Icon name="x-mark" class="size-4" />
                    取消目前勾選
                  </button>
                </div>
              </form>
            </div>
          </div>
        </aside>

        <section class="space-y-4 lg:col-span-8 xl:col-span-9">
          <h3 class="text-lg font-semibold text-theme-800 dark:text-zinc-200">
            所選來源公告
          </h3>

          <div
            v-if="Object.keys(state.selected).length > 0"
            class="mt-4 flex flex-col gap-y-1 text-sm text-theme-700 dark:text-zinc-400"
          >
            <span class="font-medium">目前條件：</span>

            <div class="flex flex-wrap items-center gap-2">
              <template
                v-for="(
                  selectedCategories, selectedSource
                ) in displaySelectedSourceCategories"
                :key="selectedSource"
              >
                <span
                  class="rounded-full border border-theme-200 bg-theme-100 px-3 py-1 font-medium text-theme-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  {{ selectedSource }}
                </span>

                <span
                  v-for="selectedCategory in selectedCategories"
                  :key="selectedCategory"
                  class="rounded-full bg-theme-100 px-3 py-1 font-medium text-theme-700 dark:bg-theme-900/60 dark:text-theme-300"
                >
                  {{ selectedCategory }}
                </span>
              </template>
            </div>
          </div>
          <div
            v-else-if="totalSelectedCategories > 0"
            class="mt-4 text-sm text-theme-700 dark:text-zinc-400"
          >
            目前條件：已勾選 {{ totalSelectedCategories }} 個分類
          </div>

          <div class="space-y-4">
            <article
              v-for="announcement in viewModel.announcements.data"
              :key="announcement.url"
              class="rounded-lg border border-theme-200 bg-white p-5 transition hover:border-theme-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
            >
              <div
                class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
              >
                <div class="min-w-0 flex-1 space-y-3">
                  <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span
                      class="rounded-full bg-theme-100 px-3 py-1 font-medium text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
                    >
                      {{ announcement.source_name }}
                    </span>

                    <span
                      class="rounded-full bg-theme-100 px-3 py-1 font-medium text-theme-700 dark:bg-theme-900/60 dark:text-theme-300"
                    >
                      {{ announcement.category }}
                    </span>

                    <span
                      v-if="isExpired(announcement)"
                      class="rounded-full bg-zinc-200 px-3 py-1 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                    >
                      已過期
                    </span>
                  </div>

                  <h3
                    class="min-w-0 text-xl leading-8 font-semibold text-theme-900 dark:text-zinc-100"
                  >
                    <a
                      :href="announcement.url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="line-clamp-2! block max-w-full align-middle break-all transition hover:text-theme-800 dark:hover:text-theme-400"
                    >
                      <span
                        v-for="tag in announcement.tags ?? []"
                        :key="tag"
                        class="mr-1 truncate rounded border border-theme-200 px-1 py-0.5 text-sm text-theme-700 dark:border-zinc-700 dark:text-zinc-400"
                        >{{ tag }}</span
                      >

                      {{ announcement.title }}
                    </a>
                  </h3>
                </div>

                <div
                  class="flex shrink-0 flex-col items-start gap-3 lg:items-end"
                >
                  <p class="text-xs text-theme-700 dark:text-zinc-400">
                    <span class="sr-only">發布時間：</span>
                    {{ formatDate(announcement.published_at) }}
                  </p>
                </div>
              </div>
            </article>

            <div
              v-if="viewModel.announcements.data.length === 0"
              class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
            >
              <div
                class="flex min-h-56 flex-col items-center justify-center gap-3 text-center"
              >
                <Icon
                  name="inbox"
                  class="size-10 text-theme-700 dark:text-zinc-400"
                />
                <div class="space-y-1">
                  <h3
                    class="text-xl font-semibold text-theme-800 dark:text-zinc-200"
                  >
                    目前沒有符合條件的公告
                  </h3>
                  <p class="text-sm text-theme-700 dark:text-zinc-400">
                    可以調整來源或分類，或稍後再回來檢視。
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="viewModel.announcements.last_page > 1"
            class="rounded-lg border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div
              class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
              <p class="text-sm text-theme-700 dark:text-zinc-400">
                第 {{ viewModel.announcements.current_page }} /
                {{ viewModel.announcements.last_page }} 頁，共
                {{ viewModel.announcements.total.toLocaleString() }} 筆結果
              </p>

              <div class="flex items-center gap-3">
                <button
                  v-if="!viewModel.announcements.prev_page_url"
                  type="button"
                  disabled
                  aria-disabled="true"
                  class="inline-flex items-center gap-2 rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 dark:border-zinc-700 dark:text-zinc-400"
                >
                  <Icon name="chevron-left" class="size-4" />
                  上一頁
                </button>
                <Link
                  v-else
                  :href="viewModel.announcements.prev_page_url"
                  class="inline-flex items-center gap-2 rounded-md border border-theme-200 px-4 py-2 text-sm font-medium text-theme-700 transition-colors hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                >
                  <Icon name="chevron-left" class="size-4" />
                  上一頁
                </Link>

                <span
                  v-if="!viewModel.announcements.next_page_url"
                  class="inline-flex items-center gap-2 rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 dark:border-zinc-700 dark:text-zinc-400"
                >
                  下一頁
                  <Icon name="chevron-right" class="size-4" />
                </span>
                <Link
                  v-else
                  :href="viewModel.announcements.next_page_url"
                  class="inline-flex items-center gap-2 rounded-md border border-theme-200 px-4 py-2 text-sm font-medium text-theme-700 transition-colors hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                >
                  下一頁
                  <Icon name="chevron-right" class="size-4" />
                </Link>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>
