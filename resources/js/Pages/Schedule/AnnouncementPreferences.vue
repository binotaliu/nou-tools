<script setup>
// Uses the `useAnnouncementPreferences` composable for the
// group/source/category checkbox tree state, and Inertia's useForm() for
// the real PUT submission (ScheduleAnnouncementPreferencesController::update()
// is a plain validate-then-redirect action).
//
// The grouped/flat catalog tree and selected-source-categories are a pure
// view-layer reshaping of `viewModel.sourceGroups` for the checkbox tree's
// convenience, so they're derived here rather than added to
// AnnouncementPreferencesPageData.
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useAnnouncementPreferences from '../../Composables/useAnnouncementPreferences'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const groupLabels = computed(() =>
  Object.fromEntries(
    props.viewModel.sourceGroups.map(g => [g.group, g.groupLabel])
  )
)

const groupedCatalogTree = computed(() =>
  Object.fromEntries(
    props.viewModel.sourceGroups.map(group => [
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

  props.viewModel.sourceGroups.forEach(group => {
    group.sources.forEach(source => {
      if (source.selectedCategories.length > 0) {
        selected[source.source] = source.selectedCategories
      }
    })
  })

  return selected
})

const {
  state,
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

const form = useForm({
  announcement_categories: {},
})

function submit() {
  form.announcement_categories = state.selected

  form.put(
    `/schedules/${props.viewModel.scheduleUuid}/announcement-preferences`,
    {
      preserveScroll: true,
    }
  )
}
</script>

<template>
  <Head
    :title="`公告分類設定 - ${viewModel.scheduleName || '我的課表'} - NOU 小幫手`"
  >
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <AppLayout>
    <div class="mx-auto max-w-4xl">
      <div
        class="mb-8 flex flex-col items-start justify-between gap-3 sm:flex-row"
      >
        <div>
          <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
            公告分類設定
          </h2>
          <p class="mt-2 text-sm text-theme-600 dark:text-zinc-400">
            選擇要在課表頁顯示的公告分類。未選擇任何分類時，課表頁的公告區塊將不顯示任何公告。
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

      <form class="space-y-6" @submit.prevent="submit">
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
                  class="size-4 rounded border-theme-300 text-theme-700 focus:ring-theme-300 dark:border-zinc-600 dark:text-zinc-300"
                  :checked="isGroupChecked(groupValue)"
                  :indeterminate="isGroupIndeterminate(groupValue)"
                  @change="toggleGroup(groupValue, $event.target.checked)"
                />
                <span
                  class="min-w-0 truncate text-sm font-semibold text-theme-900 dark:text-zinc-100"
                >
                  {{ groupLabel }}（全選）
                </span>
              </label>

              <button
                type="button"
                class="inline-flex items-center rounded-md p-2 text-theme-600 transition hover:bg-theme-100 hover:text-theme-800 dark:text-zinc-400 dark:hover:bg-zinc-900 dark:hover:text-zinc-200"
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

            <div v-show="isGroupExpanded(groupValue)" class="space-y-2 pl-2">
              <section
                v-for="(categories, source) in groupedCatalogTree[groupValue] ??
                {}"
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
                      @change="toggleSource(source, $event.target.checked)"
                    />
                    <span
                      class="min-w-0 truncate text-sm font-semibold text-theme-900 dark:text-zinc-100"
                    >
                      {{ source }}
                    </span>
                  </label>

                  <button
                    v-if="categories.length > 0"
                    type="button"
                    class="inline-flex items-center rounded-md p-2 text-theme-600 transition hover:bg-theme-100 hover:text-theme-800 dark:text-zinc-400 dark:hover:bg-zinc-900 dark:hover:text-zinc-200"
                    :aria-expanded="isSourceExpanded(source)"
                    :aria-label="`展開或收合 ${source} 分類`"
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
                      class="size-4 rounded border-theme-300 text-orange-600 focus:ring-orange-300 dark:border-zinc-600"
                      :checked="isCategoryChecked(source, category)"
                      @change="
                        toggleCategory(source, category, $event.target.checked)
                      "
                    />
                    <span class="wrap-break-word">{{ category }}</span>
                  </label>
                </div>
              </section>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-theme-600 bg-theme-600 px-4 py-2 font-semibold text-white transition hover:bg-theme-700 disabled:bg-theme-400 sm:w-auto"
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
