<script setup>
// The source/category filter panel uses the `useAnnouncementFilter`
// composable. From `lg` up it is a sticky sidebar; below that it opens as
// a bottom sheet (a modal dialog), so the list starts right under the title.
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useAnnouncementFilter from '../../Composables/useAnnouncementFilter'
import useDialogFocus from '../../Composables/useDialogFocus'

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

const selectedSourceCount = computed(() => Object.keys(state.selected).length)

const isFilterPanelOpen = ref(false)
const filterPanel = ref(null)

const { onKeydown: trapFilterPanelFocus } = useDialogFocus(
  filterPanel,
  () => isFilterPanelOpen.value
)

watch(isFilterPanelOpen, open => {
  document.documentElement.classList.toggle('overflow-hidden', open)
})

onBeforeUnmount(() => {
  document.documentElement.classList.remove('overflow-hidden')
})

function onFilterPanelKeydown(event) {
  if (!isFilterPanelOpen.value) {
    return
  }

  if (event.key === 'Escape') {
    isFilterPanelOpen.value = false
    return
  }

  trapFilterPanelFocus(event)
}

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
    <div class="mx-auto max-w-6xl space-y-5">
      <div class="flex items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          學校公告
        </h2>

        <button
          type="button"
          class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm font-medium text-theme-800 transition hover:bg-theme-50 lg:hidden dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
          :aria-expanded="isFilterPanelOpen"
          aria-controls="announcement-filter-panel"
          data-testid="announcement-filter-open"
          @click="isFilterPanelOpen = true"
        >
          <Icon name="funnel" class="size-4" />
          篩選來源
          <span
            v-if="selectedSourceCount > 0"
            class="rounded-full bg-theme-700 px-1.5 text-xs leading-5 text-white"
          >
            {{ selectedSourceCount }}
          </span>
        </button>
      </div>

      <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
        <div
          v-if="isFilterPanelOpen"
          class="fixed inset-0 z-50 bg-black/40 lg:hidden"
          aria-hidden="true"
          @click="isFilterPanelOpen = false"
        ></div>

        <aside
          id="announcement-filter-panel"
          ref="filterPanel"
          :role="isFilterPanelOpen ? 'dialog' : undefined"
          :aria-modal="isFilterPanelOpen ? 'true' : undefined"
          aria-labelledby="announcement-filter-title"
          class="lg:sticky lg:top-6 lg:col-span-4 lg:block xl:col-span-3"
          :class="
            isFilterPanelOpen
              ? 'fixed inset-x-0 bottom-0 z-50 max-h-[85dvh] lg:static lg:z-auto lg:max-h-none'
              : 'hidden'
          "
          @keydown="onFilterPanelKeydown"
        >
          <form
            method="GET"
            action="/announcements"
            class="flex max-h-[85dvh] flex-col rounded-t-2xl border border-theme-200 bg-white lg:max-h-[calc(100dvh-3rem)] lg:rounded-xl dark:border-zinc-700 dark:bg-zinc-900"
          >
            <div
              class="flex items-center justify-between gap-2 border-b border-theme-100 px-4 py-3 dark:border-zinc-800"
            >
              <h3
                id="announcement-filter-title"
                class="font-semibold text-theme-900 dark:text-zinc-100"
              >
                選擇來源
              </h3>
              <button
                type="button"
                class="-mr-1 rounded-md p-1.5 text-theme-700 hover:bg-theme-100 lg:hidden dark:text-zinc-400 dark:hover:bg-zinc-800"
                aria-label="關閉"
                @click="isFilterPanelOpen = false"
              >
                <Icon name="x-mark" class="size-5" />
              </button>
            </div>

            <div
              class="min-h-0 flex-1 divide-y divide-theme-100 overflow-y-auto overscroll-contain dark:divide-zinc-800"
            >
              <section
                v-for="(categories, source) in sourceCategoryTree"
                :key="source"
                class="px-2 py-1"
              >
                <div class="flex items-center justify-between gap-1">
                  <label
                    class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 rounded-md px-2 py-2 transition hover:bg-theme-50 dark:hover:bg-zinc-800"
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
                      class="min-w-0 truncate text-sm font-medium text-theme-900 dark:text-zinc-100"
                    >
                      {{ source }}
                    </span>
                  </label>

                  <button
                    v-if="categories.length > 0"
                    type="button"
                    class="inline-flex items-center rounded-md p-2 text-theme-700 transition hover:bg-theme-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                    :aria-expanded="isSourceExpanded(source)"
                    :aria-label="'展開或收合 ' + source + ' 分類'"
                    @click="toggleSourceExpansion(source)"
                  >
                    <Icon
                      name="chevron-down"
                      class="size-4 transition"
                      :class="isSourceExpanded(source) ? 'rotate-180' : ''"
                    />
                  </button>
                </div>

                <div
                  v-show="isSourceExpanded(source)"
                  class="grid gap-0.5 pb-1 pl-7"
                >
                  <label
                    v-for="category in categories"
                    :key="category"
                    class="flex min-w-0 cursor-pointer items-start gap-2 rounded-md px-2 py-1.5 text-sm text-theme-700 transition hover:bg-theme-50 dark:text-zinc-300 dark:hover:bg-zinc-800"
                  >
                    <input
                      type="checkbox"
                      :name="`source_categories[${source}][]`"
                      :value="category"
                      class="mt-0.5 size-4 rounded border-theme-300 text-theme-700 focus:ring-theme-300 dark:border-zinc-600"
                      :checked="isCategoryChecked(source, category)"
                      :aria-label="category"
                      @change="
                        toggleCategory(source, category, $event.target.checked)
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
              class="flex gap-2 border-t border-theme-100 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] lg:pb-3 dark:border-zinc-800"
            >
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-md border border-theme-200 px-3 py-2 text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                @click="state.selected = {}"
              >
                取消勾選
              </button>
              <button
                type="submit"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-md bg-theme-800 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-theme-900 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
              >
                <Icon name="funnel" class="size-4" />
                套用篩選
              </button>
            </div>
          </form>
        </aside>

        <section class="space-y-3 lg:col-span-8 xl:col-span-9">
          <div
            v-if="selectedSourceCount > 0"
            class="flex items-center gap-2 text-sm"
          >
            <div
              class="-my-1 flex min-w-0 flex-1 gap-1.5 overflow-x-auto py-1 lg:flex-wrap"
            >
              <span class="sr-only">目前條件：</span>
              <template
                v-for="(
                  selectedCategories, selectedSource
                ) in displaySelectedSourceCategories"
                :key="selectedSource"
              >
                <span
                  class="shrink-0 rounded-full bg-theme-100 px-2.5 py-0.5 font-medium whitespace-nowrap text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  {{ selectedSource
                  }}<template v-if="selectedCategories.length > 0">
                    · {{ selectedCategories.join('、') }}</template
                  >
                </span>
              </template>
            </div>
            <Link
              href="/announcements"
              class="shrink-0 font-medium text-theme-700 hover:underline dark:text-zinc-300"
            >
              清除
            </Link>
          </div>

          <div
            v-if="viewModel.announcements.data.length === 0"
            class="flex min-h-56 flex-col items-center justify-center gap-3 rounded-xl border border-theme-200 bg-white p-6 text-center dark:border-zinc-700 dark:bg-zinc-900"
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

          <ul
            v-else
            class="divide-y divide-theme-100 overflow-hidden rounded-xl border border-theme-200 bg-white dark:divide-zinc-800 dark:border-zinc-700 dark:bg-zinc-900"
          >
            <li
              v-for="announcement in viewModel.announcements.data"
              :key="announcement.url"
            >
              <article
                class="relative space-y-1 px-4 py-3 transition hover:bg-theme-50 sm:px-5 dark:hover:bg-zinc-800/60"
                :class="{ 'opacity-60': isExpired(announcement) }"
              >
                <p
                  class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-theme-600 dark:text-zinc-500"
                >
                  <span class="font-medium text-theme-800 dark:text-zinc-300">
                    {{ announcement.source_name }}
                  </span>
                  <span aria-hidden="true">·</span>
                  <span>{{ announcement.category }}</span>
                  <span aria-hidden="true">·</span>
                  <span>
                    <span class="sr-only">發布時間：</span>
                    {{ formatDate(announcement.published_at) }}
                  </span>
                  <span
                    v-if="isExpired(announcement)"
                    class="rounded bg-zinc-200 px-1.5 py-0.5 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                  >
                    已過期
                  </span>
                </p>

                <h3
                  class="font-medium text-theme-900 sm:text-lg dark:text-zinc-100"
                >
                  <a
                    :href="announcement.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="line-clamp-2! break-all after:absolute after:inset-0 hover:text-theme-800 dark:hover:text-theme-400"
                  >
                    <span
                      v-for="tag in announcement.tags ?? []"
                      :key="tag"
                      class="mr-1 rounded border border-theme-200 px-1 py-0.5 align-middle text-xs text-theme-700 dark:border-zinc-700 dark:text-zinc-400"
                      >{{ tag }}</span
                    >{{ announcement.title }}
                  </a>
                </h3>
              </article>
            </li>
          </ul>

          <nav
            v-if="viewModel.announcements.last_page > 1"
            class="flex items-center justify-between gap-3"
            aria-label="分頁"
          >
            <Link
              v-if="viewModel.announcements.prev_page_url"
              :href="viewModel.announcements.prev_page_url"
              class="inline-flex items-center gap-1 rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm font-medium text-theme-700 transition-colors hover:bg-theme-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
              <Icon name="chevron-left" class="size-4" />
              上一頁
            </Link>
            <span
              v-else
              aria-disabled="true"
              class="inline-flex items-center gap-1 rounded-lg border border-theme-200 px-3 py-2 text-sm text-theme-700 opacity-50 dark:border-zinc-700 dark:text-zinc-400"
            >
              <Icon name="chevron-left" class="size-4" />
              上一頁
            </span>

            <p class="text-center text-sm text-theme-700 dark:text-zinc-400">
              {{ viewModel.announcements.current_page }} /
              {{ viewModel.announcements.last_page }}
              <span class="hidden sm:inline">
                ・共
                {{ viewModel.announcements.total.toLocaleString() }} 筆
              </span>
            </p>

            <Link
              v-if="viewModel.announcements.next_page_url"
              :href="viewModel.announcements.next_page_url"
              class="inline-flex items-center gap-1 rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm font-medium text-theme-700 transition-colors hover:bg-theme-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
              下一頁
              <Icon name="chevron-right" class="size-4" />
            </Link>
            <span
              v-else
              aria-disabled="true"
              class="inline-flex items-center gap-1 rounded-lg border border-theme-200 px-3 py-2 text-sm text-theme-700 opacity-50 dark:border-zinc-700 dark:text-zinc-400"
            >
              下一頁
              <Icon name="chevron-right" class="size-4" />
            </span>
          </nav>
        </section>
      </div>
    </div>
  </AppLayout>
</template>
