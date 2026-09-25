<script setup>
// Filtering and pagination are pure client-side derived state
// (search/category/type/city filters + pagination over an already
// server-sorted list), implemented as computed properties rather than
// kept as backend logic.
import { computed, ref } from 'vue'
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

function applyFilters() {
  page.value = 1
}

function clearFilters() {
  search.value = ''
  category.value = ''
  type.value = ''
  city.value = ''
  page.value = 1
}

function goToPage(target) {
  page.value = Math.min(totalPages.value, Math.max(1, target))
}
</script>

<template>
  <Head title="優惠店家 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-6xl space-y-6">
      <div
        class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
      >
        <div class="space-y-2">
          <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
            優惠店家
          </h2>
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            適用於空大學生的優惠店家列表，歡迎回報或新增店家資訊。
            <br />
            此區資料由 <strong>112姍姍</strong> 同學維護。
          </p>
        </div>
        <Link
          href="/discount-stores/create"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800"
        >
          <Icon name="plus" class="size-4" />
          新增優惠店家
        </Link>
      </div>

      <div
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent>
          <div>
            <label
              for="search"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              搜尋
            </label>
            <input
              id="search"
              v-model.trim="search"
              type="text"
              name="search"
              accesskey="8"
              placeholder="店家名稱..."
              class="w-full rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-700"
              @input="applyFilters()"
            />
          </div>
          <div>
            <label
              for="category"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              分類
            </label>
            <div class="relative">
              <select
                id="category"
                v-model="category"
                name="category"
                class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-700 dark:bg-zinc-900"
                @change="applyFilters()"
              >
                <option value="">全部分類</option>
                <option
                  v-for="storeCategory in viewModel.categories"
                  :key="storeCategory.id"
                  :value="String(storeCategory.id)"
                >
                  {{ storeCategory.name }}
                </option>
              </select>
              <div
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
              >
                <Icon name="chevron-down" class="size-5 text-zinc-400" />
              </div>
            </div>
          </div>
          <div>
            <label
              for="type"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              類型
            </label>
            <div class="relative">
              <select
                id="type"
                v-model="type"
                name="type"
                class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-700 dark:bg-zinc-900"
                @change="applyFilters()"
              >
                <option value="">全部類型</option>
                <option
                  v-for="storeType in viewModel.types"
                  :key="storeType.value"
                  :value="storeType.value"
                >
                  {{ storeType.label }}
                </option>
              </select>
              <div
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
              >
                <Icon name="chevron-down" class="size-5 text-zinc-400" />
              </div>
            </div>
          </div>
          <div>
            <label
              for="city"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              縣市
            </label>
            <div class="relative">
              <select
                id="city"
                v-model="city"
                name="city"
                class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-700 dark:bg-zinc-900"
                @change="applyFilters()"
              >
                <option value="">全部縣市</option>
                <option
                  v-for="cityName in viewModel.cities"
                  :key="cityName"
                  :value="cityName"
                >
                  {{ cityName }}
                </option>
              </select>
              <div
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
              >
                <Icon name="chevron-down" class="size-5 text-zinc-400" />
              </div>
            </div>
          </div>
          <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
            <button
              type="submit"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800"
              @click="applyFilters()"
            >
              <Icon name="funnel" class="size-4" />
              篩選
            </button>
            <button
              v-show="hasFilters"
              type="button"
              class="inline-flex items-center justify-center gap-2 text-theme-700 hover:text-theme-800"
              @click.prevent="clearFilters()"
            >
              清除條件
            </button>
          </div>
        </form>
      </div>

      <div class="space-y-4">
        <div
          v-if="filteredStores.length === 0"
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div
            class="flex min-h-56 flex-col items-center justify-center gap-3 text-center"
          >
            <Icon
              name="building-storefront"
              class="size-10 text-theme-700 dark:text-zinc-400"
            />
            <div class="space-y-1">
              <h3
                class="text-xl font-semibold text-theme-800 dark:text-zinc-200"
              >
                目前沒有符合條件的優惠店家
              </h3>
              <p class="text-sm text-theme-700 dark:text-zinc-400">
                可以調整篩選條件，或新增一個優惠店家！
              </p>
            </div>
          </div>
        </div>

        <div
          v-for="(store, index) in visibleStores"
          :id="`store-${store.id}`"
          :key="store.id"
          :data-index="index"
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="flex flex-col gap-3">
            <div class="min-w-0 flex-1 space-y-2">
              <div class="flex flex-wrap items-center gap-2 text-sm">
                <span
                  class="inline-flex items-center gap-1 rounded-full bg-theme-100 px-3 py-1 font-medium text-theme-800 dark:bg-zinc-800 dark:text-zinc-200"
                >
                  <span class="sr-only">分類：</span>
                  <template v-if="store.category">
                    <DynamicHeroIcon
                      :name="store.category.icon"
                      class="inline-block size-4"
                    />
                    {{ store.category.name }}
                  </template>
                  <template v-else>未分類</template>
                </span>
                <span
                  class="rounded-full bg-theme-100 px-3 py-1 font-medium text-theme-700 dark:bg-theme-900/60 dark:text-theme-300"
                >
                  <span class="sr-only">類型：</span>{{ store.typeLabel }}
                </span>

                <span
                  v-if="store.city"
                  class="text-theme-700 dark:text-zinc-400"
                >
                  <span class="sr-only">地點：</span>{{ store.city }}
                  {{ store.district }}
                </span>

                <span
                  v-if="store.expiresAtDate"
                  class="rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700 dark:bg-amber-950/60 dark:text-amber-300"
                >
                  將於 {{ store.expiresAtDate }} 到期
                </span>
              </div>

              <div
                class="flex flex-col items-start justify-between md:flex-row md:items-center"
              >
                <div class="min-w-0 flex-1 flex-col">
                  <h3
                    class="truncate text-xl font-semibold text-theme-900 dark:text-zinc-100"
                  >
                    <Link
                      :href="`/discount-stores/${store.id}`"
                      class="hover:underline"
                    >
                      {{ store.name }}
                    </Link>
                  </h3>

                  <p
                    class="line-clamp-2 text-sm text-theme-700 dark:text-zinc-400"
                  >
                    {{ store.discountDetails }}
                  </p>
                </div>

                <Link
                  :href="`/discount-stores/${store.id}`"
                  :aria-label="`檢視 ${store.name} 詳情`"
                  class="hidden! items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 text-theme-900 transition hover:bg-theme-50 md:inline-flex! dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                >
                  <Icon name="eye" class="size-4" />
                  檢視詳情
                </Link>
              </div>
            </div>

            <div class="mb-4 md:mb-0">
              <span
                v-if="store.latestReportIsValid === null"
                class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300"
              >
                <Icon name="question-mark-circle" class="size-4" />
                <span class="sr-only">狀態：</span>
                尚無回報
              </span>
              <span
                v-else-if="store.latestReportIsValid"
                class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800 dark:bg-green-950/60 dark:text-green-300"
              >
                <Icon name="check-circle" class="size-4" />
                <span class="sr-only">狀態：</span>
                有效 –
                <span :title="store.latestReportCreatedAtDateTime">
                  {{ store.latestReportCreatedAtDate }}
                </span>
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800 dark:bg-red-950/60 dark:text-red-300"
              >
                <Icon name="x-circle" class="size-4" />
                <span class="sr-only">狀態：</span>
                此優惠似乎無法使用
              </span>
            </div>

            <Link
              :href="`/discount-stores/${store.id}`"
              :aria-label="`檢視 ${store.name} 詳情`"
              class="flex! items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 text-theme-900 transition hover:bg-theme-50 md:hidden! dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
              <Icon name="eye" class="size-4" />
              檢視詳情
            </Link>
          </div>
        </div>
      </div>

      <div
        v-show="filteredStores.length > perPage"
        class="rounded-lg border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div
          class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            第 {{ page }} / {{ totalPages }} 頁，共
            {{ filteredStores.length.toLocaleString() }} 筆結果
          </p>

          <div class="flex items-center gap-3">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-400"
              :disabled="page === 1"
              @click.prevent="goToPage(page - 1)"
            >
              <Icon name="chevron-left" class="size-4" />
              上一頁
            </button>

            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-400"
              :disabled="page === totalPages"
              @click.prevent="goToPage(page + 1)"
            >
              下一頁
              <Icon name="chevron-right" class="size-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
