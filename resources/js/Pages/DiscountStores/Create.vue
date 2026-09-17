<script setup>
// City/district cascading selects are pure derived UI state, implemented as
// a computed property. The Cloudflare Turnstile widget is rendered
// explicitly through the shared useTurnstile() composable (see Show.vue for
// the other two forms that need the same widget).
import { computed, nextTick, onMounted, onUnmounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import useTurnstile from '../../Composables/useTurnstile'

const props = defineProps({
  categories: {
    type: Array,
    required: true,
  },
  types: {
    type: Array,
    required: true,
  },
  cities: {
    type: Array,
    required: true,
  },
  districtsByCity: {
    type: Object,
    required: true,
  },
  turnstileSiteKey: {
    type: String,
    required: true,
  },
})

const form = useForm({
  name: '',
  type: '',
  category_id: '',
  city: '',
  district: '',
  address: '',
  verification_method: '',
  discount_details: '',
  notes: '',
  tested_valid: false,
  'cf-turnstile-response': '',
})

const districts = computed(() => props.districtsByCity[form.city] ?? [])

function handleTypeChange() {
  form.city = ''
  form.district = ''
}

function handleCityChange() {
  form.district = ''
}

// Refs returned by a composable only auto-unwrap in the template when bound
// as top-level `<script setup>` bindings, not as nested object properties
// (e.g. `turnstile.challengeExecuted` would render the Ref itself) -- so
// destructure here rather than keeping the `turnstile` object around.
const {
  container: turnstileContainer,
  challengeExecuted: turnstileChallengeExecuted,
  render: renderTurnstileWidget,
  remove: removeTurnstileWidget,
} = useTurnstile()

async function renderTurnstile() {
  await renderTurnstileWidget(props.turnstileSiteKey, {
    language: 'zh-tw',
    onSuccess: token => {
      form['cf-turnstile-response'] = token
    },
    onInvalid: () => {
      form['cf-turnstile-response'] = ''
    },
  })
}

onMounted(async () => {
  await nextTick()
  renderTurnstile()
})

onUnmounted(() => {
  removeTurnstileWidget()
})

function submit() {
  form.post('/discount-stores', {
    onError: () => {
      removeTurnstileWidget()
      form['cf-turnstile-response'] = ''
      renderTurnstile()
    },
  })
}
</script>

<template>
  <Head title="新增優惠店家 - NOU 小幫手">
    <meta name="description" content="送出新的學生優惠店家資訊。" />
  </Head>

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <div class="space-y-2">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          新增優惠店家
        </h2>
        <p class="text-sm text-theme-600 dark:text-zinc-400">
          填寫下方表單來送出新的學生優惠店家。送出後需經管理員確認才會顯示在前台。
        </p>
      </div>

      <div
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <form class="space-y-6" @submit.prevent="submit">
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-theme-900 dark:text-zinc-100">
              基本資料
            </h3>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label
                  for="name"
                  class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
                >
                  店家名稱
                  <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  name="name"
                  class="w-full rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                  placeholder="店家名稱或網站名稱"
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">
                  {{ form.errors.name }}
                </p>
              </div>

              <div>
                <label
                  for="type"
                  class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
                >
                  類型
                  <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <select
                    id="type"
                    v-model="form.type"
                    name="type"
                    class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700 dark:bg-zinc-900"
                    @change="handleTypeChange()"
                  >
                    <option value="">請選擇</option>
                    <option
                      v-for="storeType in types"
                      :key="storeType.value"
                      :value="storeType.value"
                    >
                      {{ storeType.label }}
                    </option>
                  </select>
                  <div
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <Icon name="chevron-down" class="size-5 text-gray-400" />
                  </div>
                </div>
                <p v-if="form.errors.type" class="mt-1 text-xs text-red-500">
                  {{ form.errors.type }}
                </p>
              </div>

              <div>
                <label
                  for="category_id"
                  class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
                >
                  分類
                  <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <select
                    id="category_id"
                    v-model="form.category_id"
                    name="category_id"
                    class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700 dark:bg-zinc-900"
                  >
                    <option value="">請選擇</option>
                    <option
                      v-for="category in categories"
                      :key="category.id"
                      :value="String(category.id)"
                    >
                      {{ category.name }}
                    </option>
                  </select>
                  <div
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <Icon name="chevron-down" class="size-5 text-gray-400" />
                  </div>
                </div>
                <p
                  v-if="form.errors.category_id"
                  class="mt-1 text-xs text-red-500"
                >
                  {{ form.errors.category_id }}
                </p>
              </div>
            </div>
          </div>

          <div v-show="form.type && form.type !== 'online'" class="space-y-4">
            <h3 class="text-lg font-semibold text-theme-900 dark:text-zinc-100">
              地點資訊
            </h3>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label
                  for="city"
                  class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
                >
                  縣市
                  <span v-show="form.type === 'local'" class="text-red-500">
                    *
                  </span>
                </label>
                <div class="relative">
                  <select
                    id="city"
                    v-model="form.city"
                    name="city"
                    class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700 dark:bg-zinc-900"
                    @change="handleCityChange()"
                  >
                    <option value="">請選擇</option>
                    <option
                      v-for="cityName in cities"
                      :key="cityName"
                      :value="cityName"
                    >
                      {{ cityName }}
                    </option>
                  </select>
                  <div
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <Icon name="chevron-down" class="size-5 text-gray-400" />
                  </div>
                </div>
                <p v-if="form.errors.city" class="mt-1 text-xs text-red-500">
                  {{ form.errors.city }}
                </p>
              </div>

              <div>
                <label
                  for="district"
                  class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
                >
                  鄉鎮市區
                  <span v-show="form.type === 'local'" class="text-red-500">
                    *
                  </span>
                </label>
                <div class="relative">
                  <select
                    id="district"
                    v-model="form.district"
                    name="district"
                    class="w-full appearance-none rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700 dark:bg-zinc-900"
                  >
                    <option value="">請選擇</option>
                    <option
                      v-for="districtName in districts"
                      :key="districtName"
                      :value="districtName"
                    >
                      {{ districtName }}
                    </option>
                  </select>
                  <div
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <Icon name="chevron-down" class="size-5 text-gray-400" />
                  </div>
                </div>
                <p
                  v-if="form.errors.district"
                  class="mt-1 text-xs text-red-500"
                >
                  {{ form.errors.district }}
                </p>
              </div>
            </div>
          </div>

          <div>
            <label
              for="address"
              class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              {{ form.type === 'online' ? '網址' : '詳細地址' }}
            </label>
            <input
              id="address"
              v-model="form.address"
              type="text"
              name="address"
              class="w-full rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
              :placeholder="form.type === 'online' ? 'https://...' : '詳細地址'"
            />
            <p v-if="form.errors.address" class="mt-1 text-xs text-red-500">
              {{ form.errors.address }}
            </p>
          </div>

          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-theme-900 dark:text-zinc-100">
              優惠資訊
            </h3>
            <div>
              <label
                for="verification_method"
                class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
              >
                驗證方式
              </label>
              <input
                id="verification_method"
                v-model="form.verification_method"
                type="text"
                name="verification_method"
                class="w-full rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                placeholder="例如：學生信箱、學生證、學生證+選課卡"
              />
              <p
                v-if="form.errors.verification_method"
                class="mt-1 text-xs text-red-500"
              >
                {{ form.errors.verification_method }}
              </p>
            </div>

            <div>
              <label
                for="discount_details"
                class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
              >
                優惠內容
                <span class="text-red-500">*</span>
              </label>
              <textarea
                id="discount_details"
                v-model="form.discount_details"
                name="discount_details"
                rows="3"
                class="w-full rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                placeholder="描述詳細的優惠內容..."
              ></textarea>
              <p
                v-if="form.errors.discount_details"
                class="mt-1 text-xs text-red-500"
              >
                {{ form.errors.discount_details }}
              </p>
            </div>

            <div>
              <label
                for="notes"
                class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
              >
                備註
              </label>
              <textarea
                id="notes"
                v-model="form.notes"
                name="notes"
                rows="2"
                class="w-full rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                placeholder="其他補充說明（選填）"
              ></textarea>
              <p v-if="form.errors.notes" class="mt-1 text-xs text-red-500">
                {{ form.errors.notes }}
              </p>
            </div>
          </div>

          <div>
            <label class="flex items-start gap-2">
              <input
                id="tested_valid"
                v-model="form.tested_valid"
                type="checkbox"
                name="tested_valid"
                class="mt-0.5 rounded border-theme-300 text-orange-500 focus:ring-orange-300 dark:border-zinc-700"
              />
              <span class="text-sm text-theme-700 dark:text-zinc-300">
                我已實際測試過，確認此優惠資訊正確有效
              </span>
            </label>
            <p
              v-if="form.errors.tested_valid"
              class="mt-1 text-xs text-red-500"
            >
              {{ form.errors.tested_valid }}
            </p>
          </div>

          <div>
            <div ref="turnstileContainer"></div>
            <p
              v-if="form.errors['cf-turnstile-response']"
              class="mt-1 text-xs text-red-500"
            >
              {{ form.errors['cf-turnstile-response'] }}
            </p>
          </div>

          <div class="flex items-center gap-3">
            <button
              type="submit"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-700 bg-theme-700 px-4 py-2 font-semibold text-white transition hover:bg-theme-800 disabled:cursor-not-allowed disabled:bg-theme-600"
              :disabled="form.processing || !turnstileChallengeExecuted"
            >
              <Icon name="paper-airplane" class="size-4" />
              送出
            </button>
            <Link
              href="/discount-stores"
              class="inline-flex items-center justify-center gap-2 rounded-lg border border-theme-500 bg-white px-4 py-2 text-theme-900 transition hover:bg-theme-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
              取消
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
