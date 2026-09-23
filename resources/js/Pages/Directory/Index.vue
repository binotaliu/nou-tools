<script setup>
// The 學習指導中心 map picker logic below is inline rather than a shared
// composable, since it's one-off to this page. Leaflet itself is loaded
// lazily via a dynamic import, so it's only fetched when this page needs it.
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'

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
})

const centersByRegion = computed(() => {
  const centers = props.viewModel.centerGroup?.centers ?? []
  const groups = new Map()

  centers.forEach((center, index) => {
    const key = center.regionLabel
    const entry = groups.get(key) ?? { label: key, centers: [] }
    entry.centers.push({ ...center, key: String(index) })
    groups.set(key, entry)
  })

  return [...groups.values()]
})

const allCenters = computed(() =>
  centersByRegion.value.flatMap(region => region.centers)
)

// --- offline banner (listens to real online/offline browser events;
// the site-wide link-disabling behavior in app.js does the same,
// independently) ---
const offline = ref(typeof navigator !== 'undefined' && !navigator.onLine)

function setOffline(value) {
  offline.value = value
}

function handleOnline() {
  setOffline(false)
}

function handleOffline() {
  setOffline(true)
}

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
})

onUnmounted(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
})

// --- 學習指導中心 map picker ---
const selectedKey = ref(null)
const mapContainer = ref(null)
const showMapSelectionModal = ref(false)
let leaflet = null
let map = null
let marker = null
let mapInitialized = false

const selectedCenter = computed(
  () =>
    allCenters.value.find(center => center.key === selectedKey.value) ?? null
)

async function loadLeaflet() {
  if (leaflet) {
    return leaflet
  }

  const mod = await import('../../leaflet.js')
  leaflet = window.leaflet ?? mod.default

  return leaflet
}

async function initMap() {
  if (
    mapInitialized ||
    !mapContainer.value ||
    !selectedCenter.value ||
    offline.value
  ) {
    return
  }

  const L = await loadLeaflet()

  if (!mapContainer.value || !selectedCenter.value) {
    return
  }

  mapInitialized = true

  map = L.map(mapContainer.value, {
    zoomControl: true,
    boxZoom: true,
    doubleClickZoom: false,
    dragging: true,
    keyboard: false,
    scrollWheelZoom: true,
    touchZoom: true,
  }).setView(
    [selectedCenter.value.latitude, selectedCenter.value.longitude],
    16
  )

  marker = L.marker([
    selectedCenter.value.latitude,
    selectedCenter.value.longitude,
  ]).addTo(map)

  marker.bindPopup(selectedCenter.value.name)

  L.tileLayer(props.mapTileLayer, {
    attribution: props.mapTileLayerAttribution,
  }).addTo(map)
}

async function selectCenter(key) {
  selectedKey.value = key

  if (!selectedCenter.value) {
    return
  }

  const latlng = [selectedCenter.value.latitude, selectedCenter.value.longitude]

  if (!mapInitialized) {
    await nextTick()
    await initMap()
    return
  }

  map.setView(latlng, 16)
  marker.setLatLng(latlng)
  marker.bindPopup(selectedCenter.value.name)
}

function openMapSelectionModal() {
  if (!selectedCenter.value?.latitude || !selectedCenter.value?.longitude) {
    return
  }

  showMapSelectionModal.value = true
}

function closeMapSelectionModal() {
  showMapSelectionModal.value = false
}

function openInMap(mapService, overrideUrl = null) {
  const lat = selectedCenter.value.latitude
  const lon = selectedCenter.value.longitude
  const label = encodeURIComponent(selectedCenter.value.name)

  let url = ''

  switch (mapService) {
    case 'osm':
      url = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}&zoom=16&layers=M`
      break
    case 'apple':
      url = `maps://maps.apple.com/?q=${label}&ll=${lat},${lon}&z=16`
      break
    case 'google':
      url =
        overrideUrl ??
        `https://maps.google.com/maps?q=${label}@${lat},${lon}&z=16`
      break
  }

  if (url) {
    window.open(url, '_blank')
    closeMapSelectionModal()
  }
}

onMounted(() => {
  if (window.leaflet) {
    initMap()
  }
})
</script>

<template>
  <Head title="連結 / 學習指導中心目錄 - NOU 小幫手" />

  <AppLayout>
    <div class="mx-auto max-w-6xl space-y-6">
      <div
        v-show="offline"
        class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 print:hidden"
        role="status"
        aria-live="polite"
      >
        <Icon name="signal-slash" class="mt-0.5 size-5 shrink-0" />
        <div>
          <p class="font-semibold">目前處於離線狀態</p>
          <p class="mt-1">
            這是先前載入過的快取內容，可能不是最新資料。學習指導中心地圖需要連線才能顯示，暫時已隱藏。
          </p>
        </div>
      </div>

      <div class="space-y-2">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          連結 / 學習指導中心目錄
        </h2>
        <p class="text-sm text-theme-600 dark:text-zinc-400">
          彙整校內各處室、學系與學習指導中心的官方網站連結。
        </p>
      </div>

      <div class="space-y-6">
        <div
          v-for="linkGroup in viewModel.linkGroups"
          :key="linkGroup.group"
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2
              class="mb-1 text-xl font-semibold text-theme-900 dark:text-zinc-100"
            >
              {{ linkGroup.label }}
            </h2>
          </div>

          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <a
              v-for="link in linkGroup.links"
              :key="link.name"
              :href="link.url"
              target="_blank"
              rel="noopener noreferrer"
              data-offline-allow
              class="flex items-center justify-between gap-2 rounded-lg border border-theme-200 bg-white px-4 py-3 text-sm font-medium text-theme-800 transition hover:border-theme-300 hover:bg-theme-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-950"
            >
              <span class="truncate">{{ link.name }}</span>
              <Icon
                name="arrow-top-right-on-square"
                class="size-4 shrink-0 text-theme-400 dark:text-zinc-500"
              />
            </a>
          </div>
        </div>

        <div
          v-if="viewModel.centerGroup"
          class="rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="mb-4">
            <h2
              class="mb-1 text-xl font-semibold text-theme-900 dark:text-zinc-100"
            >
              {{ viewModel.centerGroup.label }}
            </h2>
          </div>

          <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_2fr]">
            <select
              :value="selectedKey"
              data-testid="center-select"
              class="w-full rounded-lg border border-theme-200 bg-white px-3 py-2 text-sm font-medium text-theme-800 sm:hidden dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
              @change="selectCenter($event.target.value)"
            >
              <option value="">請選擇學習指導中心</option>
              <optgroup
                v-for="region in centersByRegion"
                :key="region.label"
                :label="region.label"
              >
                <option
                  v-for="center in region.centers"
                  :key="center.key"
                  :value="center.key"
                >
                  {{ center.name }}
                </option>
              </optgroup>
            </select>

            <div
              class="hidden auto-rows-min gap-3 overflow-y-auto sm:grid lg:gap-2"
            >
              <div
                v-for="region in centersByRegion"
                :key="region.label"
                class="space-y-1"
              >
                <p
                  class="px-3 text-xs font-semibold tracking-wide text-theme-500 uppercase dark:text-zinc-500"
                >
                  {{ region.label }}
                </p>
                <div class="grid grid-cols-3 gap-1 lg:grid-cols-1 lg:gap-0.5">
                  <button
                    v-for="center in region.centers"
                    :key="center.key"
                    type="button"
                    :class="
                      selectedKey === center.key
                        ? 'bg-theme-800 text-white dark:bg-zinc-100 dark:text-zinc-900'
                        : 'text-theme-700 hover:bg-theme-100 dark:text-zinc-300 dark:hover:bg-zinc-800'
                    "
                    class="truncate rounded-lg px-3 py-2 text-left text-sm font-medium transition"
                    :data-testid="'center-button-' + center.key"
                    @click="selectCenter(center.key)"
                  >
                    {{ center.name }}
                  </button>
                </div>
              </div>
            </div>

            <div class="space-y-3">
              <div
                v-if="selectedCenter"
                class="space-y-3"
                data-testid="center-details"
              >
                <div
                  v-show="!offline"
                  ref="mapContainer"
                  data-testid="center-map"
                  class="h-80 w-full rounded-lg border border-theme-100 dark:border-zinc-800"
                ></div>

                <div
                  v-show="offline"
                  data-testid="center-map-offline-notice"
                  class="flex h-80 w-full flex-col items-center justify-center gap-2 rounded-lg border border-theme-100 px-4 text-center text-sm text-theme-700 dark:border-zinc-800 dark:text-zinc-400"
                >
                  <Icon name="signal-slash" class="size-6 shrink-0" />
                  <p>目前處於離線狀態，學習指導中心地圖需要連線才能顯示。</p>
                </div>

                <div
                  class="space-y-2 text-sm text-theme-700 dark:text-zinc-300"
                >
                  <p
                    class="text-base font-semibold text-theme-900 dark:text-zinc-100"
                  >
                    {{ selectedCenter.name }}
                  </p>

                  <p
                    v-if="selectedCenter.address"
                    class="flex items-start gap-1"
                  >
                    <Icon name="map-pin" class="mt-0.5 size-4 shrink-0" />
                    <button
                      type="button"
                      data-testid="center-address-button"
                      class="text-left text-orange-600 hover:underline"
                      :disabled="
                        !selectedCenter.latitude || !selectedCenter.longitude
                      "
                      :class="
                        selectedCenter.latitude && selectedCenter.longitude
                          ? 'cursor-pointer'
                          : 'cursor-not-allowed opacity-50'
                      "
                      @click="openMapSelectionModal()"
                    >
                      {{ selectedCenter.address }}
                    </button>
                  </p>

                  <a
                    v-for="phone in selectedCenter.phone"
                    :key="phone.link"
                    :href="'tel:' + phone.link"
                    data-offline-allow
                    class="flex items-center gap-1 hover:underline"
                  >
                    <Icon name="phone" class="size-4 shrink-0" />
                    <span>{{ phone.display }}</span>
                  </a>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                  <a
                    :href="selectedCenter.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-testid="center-website-button"
                    data-offline-allow
                    class="flex items-center justify-between gap-2 rounded-lg border border-theme-200 bg-white px-4 py-3 text-sm font-medium text-theme-800 transition hover:border-theme-300 hover:bg-theme-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-950"
                  >
                    <span class="truncate">開啟中心網站</span>
                    <Icon
                      name="arrow-top-right-on-square"
                      class="size-4 shrink-0 text-theme-400 dark:text-zinc-500"
                    />
                  </a>

                  <a
                    v-show="selectedCenter.transportUrl"
                    :href="selectedCenter.transportUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-testid="center-transport-button"
                    data-offline-allow
                    class="flex items-center justify-between gap-2 rounded-lg border border-theme-200 bg-white px-4 py-3 text-sm font-medium text-theme-800 transition hover:border-theme-300 hover:bg-theme-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-950"
                  >
                    <span class="truncate">交通資訊</span>
                    <Icon
                      name="truck"
                      class="size-4 shrink-0 text-theme-400 dark:text-zinc-500"
                    />
                  </a>
                </div>
              </div>

              <div
                v-else
                data-testid="center-placeholder"
                class="flex h-80 w-full items-center justify-center rounded-lg border border-theme-100 text-theme-700 md:text-lg dark:border-zinc-800 dark:text-zinc-500"
              >
                <span class="hidden md:inline"
                  >從左側選擇一個學習指導中心來檢視詳情</span
                >
                <span class="md:hidden"
                  >從上方選擇一個學習指導中心來檢視詳情</span
                >
              </div>
            </div>

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
                  <p class="mb-6 text-sm text-theme-600 dark:text-zinc-400">
                    選擇你慣用的地圖應用程式來檢視學習指導中心位置。
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
                      @click="
                        openInMap('google', selectedCenter?.googleMapsUrl)
                      "
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
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
