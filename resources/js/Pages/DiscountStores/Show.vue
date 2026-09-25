<script setup>
// This page combines a single-marker Leaflet map + "open in map app" picker
// with two small forms (report validity / add comment), each gated by a
// Cloudflare Turnstile challenge.
//
// The Leaflet map + map-app-selection modal stays inlined per-page (same
// choice already made, and explained, in Directory/Index.vue) rather than
// becoming a shared composable: the two implementations diverge on more than
// styling (Directory's picks from a list of centers and re-targets one
// shared map instance; this page has exactly one fixed marker and no
// picker), so extracting a common composable would mostly move code around
// without removing real duplication. The identical piece -- rendering the
// three "open in OSM / Apple / Google" links from a lat/lng pair -- is only
// ~15 lines and isn't worth a cross-page composable on its own.
//
// The Turnstile widget setup *is* extracted (useTurnstile.js), since this
// page alone needs two independent instances of the exact same
// load-script/render/track-challenge sequence, and Create.vue needs a third.
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import DynamicHeroIcon from '../../Components/DynamicHeroIcon.vue'
import useTurnstile from '../../Composables/useTurnstile'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
  mapTileLayer: {
    type: String,
    required: true,
  },
  mapTileLayerAttribution: {
    type: String,
    required: true,
  },
  turnstileSiteKey: {
    type: String,
    required: true,
  },
})

const page = usePage()
const flashSuccess = computed(() => page.props.flash?.success ?? null)

const hasCoordinates = computed(
  () => props.viewModel.latitude !== null && props.viewModel.longitude !== null
)

const shouldShowMap = computed(
  () =>
    props.viewModel.typeValue !== 'online' &&
    !!props.viewModel.address &&
    hasCoordinates.value
)

const latestReport = computed(() => props.viewModel.reports[0] ?? null)
const recentReports = computed(() => props.viewModel.reports.slice(1))
const recentReportsExpanded = ref(false)

// --- Leaflet map (single fixed marker) ---
const mapContainer = ref(null)
let leaflet = null
let mapInitialized = false

async function loadLeaflet() {
  if (leaflet) {
    return leaflet
  }

  const mod = await import('../../leaflet.js')
  leaflet = window.leaflet ?? mod.default

  return leaflet
}

async function initMap() {
  if (mapInitialized || !shouldShowMap.value || !mapContainer.value) {
    return
  }

  const L = await loadLeaflet()

  if (!mapContainer.value) {
    return
  }

  mapInitialized = true

  const map = L.map(mapContainer.value, {
    zoomControl: true,
    boxZoom: true,
    doubleClickZoom: false,
    dragging: true,
    keyboard: false,
    scrollWheelZoom: true,
    touchZoom: true,
  }).setView([props.viewModel.latitude, props.viewModel.longitude], 16)

  L.marker([props.viewModel.latitude, props.viewModel.longitude])
    .addTo(map)
    .bindPopup(props.viewModel.name)

  L.tileLayer(props.mapTileLayer, {
    attribution: props.mapTileLayerAttribution,
  }).addTo(map)
}

onMounted(() => {
  if (shouldShowMap.value) {
    initMap()
  }
})

// --- "open in map app" picker ---
const showMapSelectionModal = ref(false)

function openMapSelectionModal() {
  if (!hasCoordinates.value) {
    return
  }
  showMapSelectionModal.value = true
}

function closeMapSelectionModal() {
  showMapSelectionModal.value = false
}

function openInMap(mapService) {
  const lat = props.viewModel.latitude
  const lon = props.viewModel.longitude
  const label = encodeURIComponent(props.viewModel.name)

  let url = ''

  switch (mapService) {
    case 'osm':
      url = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}&zoom=16&layers=M`
      break
    case 'apple':
      url = `maps://maps.apple.com/?q=${label}&ll=${lat},${lon}&z=16`
      break
    case 'google':
      url = `https://maps.google.com/maps?q=${label}@${lat},${lon}&z=16`
      break
  }

  if (url) {
    window.open(url, '_blank')
    closeMapSelectionModal()
  }
}

// --- report modal + form ---
const showReportModal = ref(false)
const pendingIsValid = ref(true)

const reportForm = useForm({
  is_valid: true,
  comment: '',
  'cf-turnstile-response': '',
})

const {
  container: reportTurnstileContainer,
  challengeExecuted: reportChallengeExecuted,
  render: renderReportTurnstileWidget,
  remove: removeReportTurnstileWidget,
} = useTurnstile()

async function renderReportTurnstile() {
  await renderReportTurnstileWidget(props.turnstileSiteKey, {
    language: 'zh-tw',
    onSuccess: token => {
      reportForm['cf-turnstile-response'] = token
    },
    onInvalid: () => {
      reportForm['cf-turnstile-response'] = ''
    },
  })
}

async function openReportModal(isValid) {
  pendingIsValid.value = isValid
  reportForm.is_valid = isValid
  showReportModal.value = true
  await nextTick()
  renderReportTurnstile()
}

function closeReportModal() {
  showReportModal.value = false
  removeReportTurnstileWidget()
  reportForm.reset()
}

function submitReport() {
  reportForm.is_valid = pendingIsValid.value

  reportForm.post(`/discount-stores/${props.viewModel.id}/reports`, {
    preserveScroll: true,
    onSuccess: () => {
      closeReportModal()
    },
    onError: () => {
      removeReportTurnstileWidget()
      reportForm['cf-turnstile-response'] = ''
      renderReportTurnstile()
    },
  })
}

// --- comment modal + form ---
const showCommentModal = ref(false)

const commentForm = useForm({
  nickname: '',
  content: '',
  'cf-turnstile-response': '',
})

const {
  container: commentTurnstileContainer,
  challengeExecuted: commentChallengeExecuted,
  render: renderCommentTurnstileWidget,
  remove: removeCommentTurnstileWidget,
} = useTurnstile()

async function renderCommentTurnstile() {
  await renderCommentTurnstileWidget(props.turnstileSiteKey, {
    language: 'zh-tw',
    onSuccess: token => {
      commentForm['cf-turnstile-response'] = token
    },
    onInvalid: () => {
      commentForm['cf-turnstile-response'] = ''
    },
  })
}

async function openCommentModal() {
  showCommentModal.value = true
  await nextTick()
  renderCommentTurnstile()
}

function closeCommentModal() {
  showCommentModal.value = false
  removeCommentTurnstileWidget()
  commentForm.reset()
}

function submitComment() {
  commentForm.post(`/discount-stores/${props.viewModel.id}/comments`, {
    preserveScroll: true,
    onSuccess: () => {
      closeCommentModal()
    },
    onError: () => {
      removeCommentTurnstileWidget()
      commentForm['cf-turnstile-response'] = ''
      renderCommentTurnstile()
    },
  })
}

onUnmounted(() => {
  removeReportTurnstileWidget()
  removeCommentTurnstileWidget()
})
</script>

<template>
  <Head :title="`${viewModel.name} - 優惠店家 - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <div
        v-if="flashSuccess"
        class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300"
      >
        {{ flashSuccess }}
      </div>

      <div
        class="flex flex-col flex-wrap items-start justify-center gap-2 text-sm"
      >
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          優惠店家詳情
        </h2>
        <Link
          href="/discount-stores"
          class="inline-flex items-center gap-1 text-theme-700 transition hover:text-theme-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="chevron-left" class="size-4" />
          回到優惠店家列表
        </Link>
      </div>

      <div
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="space-y-4">
          <div class="flex flex-wrap items-center gap-2 text-sm">
            <span
              class="rounded-full bg-theme-100 px-3 py-1 font-medium text-theme-800 dark:bg-zinc-900 dark:text-zinc-200"
            >
              <DynamicHeroIcon
                v-if="viewModel.categoryIcon"
                :name="viewModel.categoryIcon"
                class="inline-block size-4"
              />
              {{ viewModel.categoryName ?? '未分類' }}
            </span>
            <span
              class="rounded-full bg-theme-100 px-3 py-1 font-medium text-theme-700 dark:bg-theme-900/60 dark:text-theme-300"
            >
              {{ viewModel.typeLabel }}
            </span>

            <span
              v-if="viewModel.city"
              class="text-theme-700 dark:text-zinc-400"
            >
              {{ viewModel.city }} {{ viewModel.district }}
            </span>

            <span
              v-if="viewModel.expiresAtDateTime"
              class="rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700 dark:bg-amber-950/60 dark:text-amber-300"
            >
              將於 {{ viewModel.expiresAtDateTime }} 到期
            </span>
          </div>

          <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
            {{ viewModel.name }}
          </h2>

          <p
            v-if="viewModel.address"
            class="flex items-center gap-1 text-sm text-theme-700 dark:text-zinc-400"
          >
            <template v-if="viewModel.typeValue === 'online'">
              <template
                v-if="
                  viewModel.address.startsWith('http://') ||
                  viewModel.address.startsWith('https://')
                "
              >
                <Icon name="globe-alt" class="inline-block size-4" />
                <a
                  :href="viewModel.address"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-theme-700 hover:underline"
                >
                  {{ viewModel.address }}
                </a>
              </template>
            </template>
            <template v-else>
              <Icon name="map-pin" class="inline-block size-4" />
              <button
                type="button"
                class="text-left text-theme-700 hover:underline"
                :disabled="!hasCoordinates"
                :class="
                  hasCoordinates
                    ? 'cursor-pointer'
                    : 'cursor-not-allowed opacity-50'
                "
                @click="openMapSelectionModal()"
              >
                {{ viewModel.address }}
              </button>
            </template>
          </p>

          <div
            v-show="shouldShowMap"
            ref="mapContainer"
            data-testid="store-map"
            class="h-80 w-full rounded-lg border border-theme-100 dark:border-zinc-800"
          ></div>

          <div class="text-sm text-theme-700 dark:text-zinc-300">
            <p class="wrap-break-word">
              <span class="font-medium">優惠內容：</span>
              <span class="whitespace-pre-line">{{
                viewModel.discountDetails
              }}</span>
            </p>

            <p v-if="viewModel.verificationMethod" class="mt-1 wrap-break-word">
              <span class="font-medium">驗證方式：</span>
              <span class="whitespace-pre-line">{{
                viewModel.verificationMethod
              }}</span>
            </p>
          </div>

          <p
            v-if="viewModel.notes"
            class="text-sm wrap-break-word text-theme-700 dark:text-zinc-400"
          >
            備註：
            <span class="whitespace-pre-line">{{ viewModel.notes }}</span>
          </p>

          <div
            class="space-y-2 rounded-lg border border-theme-100 bg-theme-50 px-4 py-3 text-sm text-theme-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300"
          >
            <div class="flex flex-wrap items-center gap-2">
              <p class="font-medium text-theme-900 dark:text-zinc-100">
                最新回報
              </p>
              <span
                v-if="latestReport"
                class="inline-flex items-center gap-1 font-medium"
                :class="
                  latestReport.isValid
                    ? 'text-green-700 dark:text-green-400'
                    : 'text-red-600 dark:text-red-400'
                "
              >
                <Icon
                  :name="latestReport.isValid ? 'check-circle' : 'x-circle'"
                  class="size-4"
                />
                {{ latestReport.isValid ? '有效' : '無效' }}
              </span>
            </div>

            <div v-if="latestReport" class="space-y-1">
              <p
                class="text-sm wrap-break-word text-theme-700 dark:text-zinc-300"
              >
                <span class="whitespace-pre-line">{{
                  latestReport.comment || '（無補充說明）'
                }}</span>
              </p>

              <p class="text-xs text-theme-700 dark:text-zinc-400">
                {{ latestReport.createdAtHuman }}
              </p>
            </div>

            <details
              v-if="latestReport && recentReports.length > 0"
              class="rounded-lg border border-theme-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
              :open="recentReportsExpanded"
              @toggle="recentReportsExpanded = $event.target.open"
            >
              <summary
                class="cursor-pointer text-sm font-medium text-theme-700 dark:text-zinc-300"
              >
                展開看更多近期回報（{{ recentReports.length }}）
              </summary>
              <div class="mt-2 space-y-2">
                <div
                  v-for="(report, index) in recentReports"
                  :key="index"
                  class="rounded-md border border-theme-100 bg-theme-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-950"
                >
                  <p
                    class="inline-flex items-center gap-1 text-sm font-medium"
                    :class="
                      report.isValid
                        ? 'text-green-700 dark:text-green-400'
                        : 'text-red-600 dark:text-red-400'
                    "
                  >
                    <Icon
                      :name="report.isValid ? 'check-circle' : 'x-circle'"
                      class="size-4"
                    />
                    {{ report.isValid ? '有效' : '無效' }}
                  </p>
                  <p
                    class="mt-1 text-sm wrap-break-word text-theme-700 dark:text-zinc-300"
                  >
                    <span class="whitespace-pre-line">{{
                      report.comment || '（無補充說明）'
                    }}</span>
                  </p>
                  <p class="mt-1 text-xs text-theme-700 dark:text-zinc-400">
                    {{ report.createdAtHuman }}
                  </p>
                </div>
              </div>
            </details>

            <p
              v-if="!latestReport"
              class="text-sm text-theme-700 dark:text-zinc-400"
            >
              目前還沒有回報資料。
            </p>
          </div>

          <div
            class="flex flex-col gap-2 border-t border-theme-100 pt-3 dark:border-zinc-800"
          >
            <p class="text-sm text-theme-700 dark:text-zinc-400">
              使用了本優惠嗎？請協助回報優惠的有效性，讓其他同學參考！
            </p>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-green-500 px-3 py-1.5 text-sm text-green-700 transition hover:bg-green-50 dark:text-green-400"
                title="回報有效"
                @click="openReportModal(true)"
              >
                <Icon name="check-circle" class="size-4" />
                回報有效
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-red-500 px-3 py-1.5 text-sm text-red-600 transition hover:bg-red-50 dark:text-red-400"
                title="回報無效"
                @click="openReportModal(false)"
              >
                <Icon name="x-circle" class="size-4" />
                回報無效
              </button>
            </div>
          </div>
        </div>
      </div>

      <Teleport to="body">
        <div
          v-if="showReportModal"
          class="fixed inset-0 z-1100 flex items-center justify-center bg-black/50"
          @click.self="closeReportModal()"
        >
          <div
            class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900"
          >
            <h3
              class="mb-4 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              回報「{{ viewModel.name }}」{{ pendingIsValid ? '有效' : '無效' }}
            </h3>
            <form class="space-y-4" @submit.prevent="submitReport">
              <div>
                <label
                  for="report-comment"
                  class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
                >
                  備註（選填）
                </label>
                <textarea
                  id="report-comment"
                  v-model="reportForm.comment"
                  rows="2"
                  class="w-full rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-700"
                  placeholder="補充說明..."
                ></textarea>
              </div>
              <div>
                <div ref="reportTurnstileContainer"></div>
                <p
                  v-if="reportForm.errors['cf-turnstile-response']"
                  class="mt-1 text-xs text-red-600 dark:text-red-400"
                >
                  {{ reportForm.errors['cf-turnstile-response'] }}
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="submit"
                  class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition disabled:bg-zinc-400"
                  :class="
                    pendingIsValid
                      ? 'bg-green-600 hover:bg-green-700'
                      : 'bg-red-600 hover:bg-red-700'
                  "
                  :disabled="reportForm.processing || !reportChallengeExecuted"
                >
                  確認回報
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                  @click="closeReportModal()"
                >
                  取消
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>

      <Teleport to="body">
        <div
          v-if="showMapSelectionModal"
          class="fixed inset-0 z-1100 flex items-center justify-center bg-black/50"
          @click.self="closeMapSelectionModal()"
        >
          <div
            class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900"
          >
            <h3
              class="mb-4 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              選擇地圖 App
            </h3>
            <p class="mb-6 text-sm text-theme-700 dark:text-zinc-400">
              選擇你慣用的地圖應用程式來檢視店家位置。
            </p>
            <div class="space-y-2">
              <button
                type="button"
                class="w-full rounded-lg border border-theme-200 px-4 py-3 text-center text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                @click="openInMap('osm')"
              >
                在 OpenStreetMap 開啟
              </button>
              <button
                type="button"
                class="w-full rounded-lg border border-theme-200 px-4 py-3 text-center text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                @click="openInMap('apple')"
              >
                在 Apple 地圖開啟
              </button>
              <button
                type="button"
                class="w-full rounded-lg border border-theme-200 px-4 py-3 text-center text-sm font-medium text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                @click="openInMap('google')"
              >
                在 Google 地圖開啟
              </button>
            </div>
            <button
              type="button"
              class="mt-4 w-full rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
              @click="closeMapSelectionModal()"
            >
              關閉
            </button>
          </div>
        </div>
      </Teleport>

      <div
        v-if="viewModel.comments.length > 0"
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="space-y-2">
          <h3
            class="flex items-center gap-1 text-base font-semibold text-theme-700 dark:text-zinc-300"
          >
            留言
            <span
              v-if="viewModel.commentsCount > 0"
              class="inline-flex items-center gap-1 rounded-full bg-theme-100 px-2 py-0.5 text-xs font-medium text-theme-800 dark:bg-zinc-900 dark:text-zinc-200"
            >
              {{ viewModel.commentsCount }}
            </span>
          </h3>
          <div
            v-for="(comment, index) in viewModel.comments"
            :key="index"
            class="rounded-lg bg-theme-50 px-3 py-2 text-sm text-theme-700 dark:bg-zinc-950 dark:text-zinc-300"
          >
            <p
              class="mb-2 text-sm font-medium text-theme-900 dark:text-zinc-100"
            >
              {{ comment.nickname }}
            </p>
            <p class="wrap-break-word whitespace-pre-line">
              {{ comment.content }}
            </p>
            <span class="text-xs text-theme-700 dark:text-zinc-400">
              — {{ comment.createdAtHuman }}
            </span>
          </div>
        </div>
      </div>

      <div
        class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="space-y-2">
          <h3 class="text-base font-semibold text-theme-700 dark:text-zinc-300">
            新增留言
          </h3>
          <p class="text-sm text-theme-700 dark:text-zinc-400">
            歡迎分享使用經驗，留言會在確認後顯示。
          </p>
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg bg-theme-800 px-3 py-2 text-sm font-medium text-white transition hover:bg-theme-900"
            @click="openCommentModal()"
          >
            <Icon name="chat-bubble-left" class="size-4" />
            新增留言
          </button>
        </div>
      </div>

      <Teleport to="body">
        <div
          v-if="showCommentModal"
          class="fixed inset-0 z-1100 flex items-center justify-center bg-black/50"
          @click.self="closeCommentModal()"
        >
          <div
            class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900"
          >
            <h3
              class="mb-4 text-lg font-semibold text-theme-900 dark:text-zinc-100"
            >
              新增留言
            </h3>
            <form class="space-y-4" @submit.prevent="submitComment">
              <div class="flex flex-col gap-2">
                <input
                  v-model="commentForm.nickname"
                  type="text"
                  class="rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-700"
                  placeholder="暱稱"
                  maxlength="100"
                  required
                />
                <textarea
                  v-model="commentForm.content"
                  class="flex-1 rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-700"
                  placeholder="留言（確認後顯示）..."
                  maxlength="1000"
                  rows="5"
                  required
                ></textarea>
              </div>
              <div>
                <div ref="commentTurnstileContainer"></div>
                <p
                  v-if="commentForm.errors['cf-turnstile-response']"
                  class="mt-1 text-xs text-red-600 dark:text-red-400"
                >
                  {{ commentForm.errors['cf-turnstile-response'] }}
                </p>
              </div>
              <p class="text-xs text-theme-700 dark:text-zinc-400">
                為避免垃圾留言，留言將由管理員確認後才會顯示出來。
              </p>
              <div class="flex items-center gap-2">
                <button
                  type="submit"
                  class="inline-flex items-center gap-1 rounded-lg bg-theme-800 px-3 py-2 text-sm font-medium text-white transition hover:bg-theme-900 disabled:bg-zinc-400"
                  :disabled="
                    commentForm.processing || !commentChallengeExecuted
                  "
                >
                  <Icon name="chat-bubble-left" class="size-4" />
                  送出
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-theme-200 px-4 py-2 text-sm text-theme-700 transition hover:bg-theme-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
                  @click="closeCommentModal()"
                >
                  取消
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>
    </div>
  </AppLayout>
</template>
