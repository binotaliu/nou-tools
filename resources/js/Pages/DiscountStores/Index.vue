<script setup>
// Filtering and pagination are pure client-side derived state
// (search/category/type/city filters + pagination over an already
// server-sorted list), implemented as computed properties rather than
// kept as backend logic.
import { computed, ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import DynamicHeroIcon from '../../Components/DynamicHeroIcon.vue'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const search = ref(props.viewModel.search ?? '')
const category = ref(
  props.viewModel.selectedCategoryId != null
    ? String(props.viewModel.selectedCategoryId)
    : ''
)
const type = ref(props.viewModel.selectedType ?? '')
const city = ref(props.viewModel.selectedCity ?? '')
const page = ref(1)
const perPage = 20

const normalizedSearch = computed(() => search.value.trim().toLowerCase())

const filteredStores = computed(() =>
  (props.viewModel.stores ?? []).filter(store => {
    const matchesSearch =
      normalizedSearch.value === '' ||
      store.name.toLowerCase().includes(normalizedSearch.value)
    const matchesCategory =
      category.value === '' ||
      String(store.categoryId) === String(category.value)
    const matchesType = type.value === '' || store.type === type.value
    const matchesCity = city.value === '' || store.city === city.value

    return matchesSearch && matchesCategory && matchesType && matchesCity
  })
)

const visibleStores = computed(() => {
  const pageStart = (page.value - 1) * perPage
  return filteredStores.value.slice(pageStart, pageStart + perPage)
})

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredStores.value.length / perPage))
)

const hasFilters = computed(
  () => search.value || category.value || type.value || city.value
)

const listTop = ref(null)

const filterSelects = computed(() => [
  {
    id: 'category',
    label: '分類',
    allLabel: '全部分類',
    model: category,
    options: (props.viewModel.categories ?? []).map(storeCategory => ({
      value: String(storeCategory.id),
      label: storeCategory.name,
    })),
  },
  {
    id: 'type',
    label: '類型',
    allLabel: '全部類型',
    model: type,
    options: (props.viewModel.types ?? []).map(storeType => ({
      value: storeType.value,
      label: storeType.label,
    })),
  },
  {
    id: 'city',
    label: '縣市',
    allLabel: '全部縣市',
    model: city,
    options: (props.viewModel.cities ?? []).map(cityName => ({
      value: cityName,
      label: cityName,
    })),
  },
])

watch([search, category, type, city], () => {
  page.value = 1
})

function clearFilters() {
  search.value = ''
  category.value = ''
  type.value = ''
  city.value = ''
  page.value = 1
}

function goToPage(target) {
  page.value = Math.min(totalPages.value, Math.max(1, target))
  listTop.value?.scrollIntoView({ block: 'start' })
}

function statusLabel(store) {
  if (store.latestReportIsValid === null) {
    return '尚無回報'
  }

  return store.latestReportIsValid
    ? `有效 · ${store.latestReportCreatedAtDate}`
    : '可能已無法使用'
}
</script>

<template>
  <Head title="優惠店家 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-4xl space-y-5">
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 space-y-1">
          <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
            優惠店家
          </h2>
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            空大學生適用的優惠，由 <strong>112姍姍</strong> 同學維護。
          </p>
        </div>
        <Link
          href="/discount-stores/create"
          class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-theme-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-theme-800"
        >
          <Icon name="plus" class="size-4" />
          <span>新增<span class="hidden sm:inline">店家</span></span>
        </Link>
      </div>

      <form class="space-y-3" role="search" @submit.prevent>
        <div class="relative">
          <label for="search" class="sr-only">搜尋店家名稱</label>
          <Icon
            name="magnifying-glass"
            class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-theme-500 dark:text-zinc-500"
          />
          <input
            id="search"
            v-model.trim="search"
            type="search"
            name="search"
            accesskey="8"
            placeholder="搜尋店家名稱"
            class="w-full rounded-xl border border-theme-200 bg-white py-2.5 pr-3 pl-10 text-base focus:border-theme-300 focus:ring-theme-300 sm:text-sm dark:border-zinc-700 dark:bg-zinc-900"
          />
        </div>

        <div
          class="-mx-6 flex gap-2 overflow-x-auto px-6 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0"
        >
          <div
            v-for="filter in filterSelects"
            :key="filter.id"
            class="relative shrink-0"
          >
            <label :for="filter.id" class="sr-only">{{ filter.label }}</label>
            <select
              :id="filter.id"
              v-model="filter.model.value"
              :name="filter.id"
              class="appearance-none rounded-full border py-1.5 pr-8 pl-3.5 text-sm font-medium transition focus:ring-theme-300"
              :class="
                filter.model.value
                  ? 'border-theme-700 bg-theme-700 text-white dark:border-theme-500 dark:bg-theme-600'
                  : 'border-theme-200 bg-white text-theme-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200'
              "
            >
              <option value="">{{ filter.allLabel }}</option>
              <option
                v-for="option in filter.options"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
            <Icon
              name="chevron-down"
              class="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2"
              :class="filter.model.value ? 'text-white' : 'text-zinc-400'"
            />
          </div>

          <button
            v-show="hasFilters"
            type="button"
            class="inline-flex shrink-0 items-center gap-1 rounded-full px-3 py-1.5 text-sm font-medium text-theme-700 hover:bg-theme-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
            @click="clearFilters()"
          >
            <Icon name="x-mark" class="size-4" />
            清除
          </button>
        </div>
      </form>

      <div ref="listTop" class="scroll-mt-20 space-y-2">
        <p
          class="text-sm text-theme-700 dark:text-zinc-400"
          aria-live="polite"
          data-testid="discount-store-count"
        >
          共 {{ filteredStores.length.toLocaleString() }} 家
        </p>

        <div
          v-if="filteredStores.length === 0"
          class="flex min-h-56 flex-col items-center justify-center gap-3 rounded-xl border border-theme-200 bg-white p-6 text-center dark:border-zinc-700 dark:bg-zinc-900"
        >
          <Icon
            name="building-storefront"
            class="size-10 text-theme-700 dark:text-zinc-400"
          />
          <div class="space-y-1">
            <h3 class="text-xl font-semibold text-theme-800 dark:text-zinc-200">
              目前沒有符合條件的優惠店家
            </h3>
            <p class="text-sm text-theme-700 dark:text-zinc-400">
              可以調整篩選條件，或新增一個優惠店家！
            </p>
          </div>
        </div>

        <ul
          v-else
          class="divide-y divide-theme-100 overflow-hidden rounded-xl border border-theme-200 bg-white dark:divide-zinc-800 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <li
            v-for="(store, index) in visibleStores"
            :id="`store-${store.id}`"
            :key="store.id"
            :data-index="index"
          >
            <Link
              :href="`/discount-stores/${store.id}`"
              class="flex items-start gap-3 px-4 py-3.5 transition hover:bg-theme-50 focus-visible:-outline-offset-2 sm:gap-4 sm:px-5 dark:hover:bg-zinc-800/60"
            >
              <span
                class="mt-0.5 hidden size-10 shrink-0 items-center justify-center rounded-lg bg-theme-100 text-theme-800 sm:flex dark:bg-zinc-800 dark:text-zinc-200"
              >
                <DynamicHeroIcon
                  v-if="store.category"
                  :name="store.category.icon"
                  class="size-5"
                />
                <Icon v-else name="building-storefront" class="size-5" />
              </span>

              <span class="min-w-0 flex-1 space-y-1">
                <span
                  class="block truncate font-semibold text-theme-900 dark:text-zinc-100"
                >
                  {{ store.name }}
                </span>
                <span
                  class="line-clamp-2 text-sm text-theme-700 dark:text-zinc-400"
                >
                  {{ store.discountDetails }}
                </span>
                <span
                  class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-theme-600 dark:text-zinc-500"
                >
                  <span
                    class="inline-flex items-center gap-1 font-medium"
                    :class="{
                      'text-green-700 dark:text-green-400':
                        store.latestReportIsValid === true,
                      'text-red-700 dark:text-red-400':
                        store.latestReportIsValid === false,
                    }"
                  >
                    <Icon
                      :name="
                        store.latestReportIsValid === null
                          ? 'question-mark-circle'
                          : store.latestReportIsValid
                            ? 'check-circle'
                            : 'x-circle'
                      "
                      class="size-3.5"
                    />
                    <span class="sr-only">狀態：</span>
                    <span :title="store.latestReportCreatedAtDateTime">{{
                      statusLabel(store)
                    }}</span>
                  </span>
                  <span aria-hidden="true">·</span>
                  <span>
                    <span class="sr-only">分類：</span
                    >{{ store.category?.name ?? '未分類' }}
                  </span>
                  <span aria-hidden="true">·</span>
                  <span>
                    <span class="sr-only">類型：</span>{{ store.typeLabel }}
                  </span>
                  <template v-if="store.city">
                    <span aria-hidden="true">·</span>
                    <span>
                      <span class="sr-only">地點：</span>{{ store.city
                      }}{{ store.district }}
                    </span>
                  </template>
                  <span
                    v-if="store.expiresAtDate"
                    class="rounded bg-amber-100 px-1.5 py-0.5 font-medium text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                  >
                    {{ store.expiresAtDate }} 到期
                  </span>
                </span>
              </span>

              <Icon
                name="chevron-right"
                class="mt-3 size-4 shrink-0 text-theme-400 dark:text-zinc-600"
              />
            </Link>
          </li>
        </ul>
      </div>

      <nav
        v-show="filteredStores.length > perPage"
        class="flex items-center justify-between gap-3"
        aria-label="分頁"
      >
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm text-theme-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"
          :disabled="page === 1"
          @click="goToPage(page - 1)"
        >
          <Icon name="chevron-left" class="size-4" />
          上一頁
        </button>

        <p class="text-sm text-theme-700 dark:text-zinc-400">
          {{ page }} / {{ totalPages }}
        </p>

        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm text-theme-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"
          :disabled="page === totalPages"
          @click="goToPage(page + 1)"
        >
          下一頁
          <Icon name="chevron-right" class="size-4" />
        </button>
      </nav>
    </div>
  </AppLayout>
</template>
