@push('head')
    @vite(['resources/js/leaflet.js'])

    <x-json-ld
        :data="[
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => '連結 / 學習指導中心目錄',
            'itemListElement' => $viewModel->linkGroups
                ->toCollection()
                ->flatMap(fn ($group) => $group->links->toCollection())
                ->concat($viewModel->centerGroup?->centers->toCollection() ?? [])
                ->values()
                ->map(fn ($link, $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => $link->url,
                    'name' => $link->name,
                ])
                ->all(),
        ]"
    />
@endpush

<x-layout
    title="連結 / 學習指導中心目錄 - NOU 小幫手"
    description="彙整校內各處室、學系與學習指導中心的官方網站連結。"
>
    <div class="mx-auto max-w-6xl space-y-6">
        <div
            x-data
            x-show="$store.network.offline"
            x-cloak
            class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 print:hidden"
            role="status"
            aria-live="polite"
        >
            <x-heroicon-o-signal-slash class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-semibold">目前處於離線狀態</p>
                <p class="mt-1">
                    這是先前載入過的快取內容，可能不是最新資料。學習指導中心地圖需要連線才能顯示，暫時已隱藏。
                </p>
            </div>
        </div>

        <div class="space-y-2">
            <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
                連結 / 學習指導中心目錄
            </h2>
            <p class="text-sm text-warm-600 dark:text-zinc-400">
                彙整校內各處室、學系與學習指導中心的官方網站連結。
            </p>
        </div>

        <div class="space-y-6">
            @foreach ($viewModel->linkGroups as $linkGroup)
                <x-card :title="$linkGroup->label">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($linkGroup->links as $link)
                            <a
                                href="{{ $link->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                data-offline-allow
                                class="flex items-center justify-between gap-2 rounded-lg border border-warm-200 bg-white px-4 py-3 text-sm font-medium text-warm-800 transition hover:border-warm-300 hover:bg-warm-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-600 dark:hover:bg-zinc-950"
                            >
                                <span class="truncate">{{ $link->name }}</span>
                                <x-heroicon-o-arrow-top-right-on-square
                                    class="size-4 shrink-0 text-warm-400 dark:text-zinc-500"
                                />
                            </a>
                        @endforeach
                    </div>
                </x-card>
            @endforeach

            @if ($viewModel->centerGroup)
                @php
                    $centerGroup = $viewModel->centerGroup;

                    $centersFrontEndData = $centerGroup->centers
                        ->toCollection()
                        ->map(
                            fn ($center, $index) => [
                                'key' => (string) $index,
                                'name' => $center->name,
                                'url' => $center->url,
                                'regionLabel' => $center->regionLabel,
                                'address' => $center->address,
                                'phones' => $center->phone
                                    ->toCollection()
                                    ->map(
                                        fn ($phone) => [
                                            'display' => $phone->display,
                                            'link' => $phone->link,
                                        ],
                                    )
                                    ->values(),
                                'latitude' => $center->latitude,
                                'longitude' => $center->longitude,
                                'transportUrl' => $center->transportUrl,
                                'googleMapsUrl' => $center->googleMapsUrl,
                            ],
                        )
                        ->values();

                    $centersByRegion = $centersFrontEndData
                        ->groupBy('regionLabel')
                        ->map(
                            fn ($centers, $regionLabel) => [
                                'label' => $regionLabel,
                                'centers' => $centers->values(),
                            ],
                        )
                        ->values();
                @endphp

                <x-card :title="$centerGroup->label">
                    <div
                        x-data="linksCenterMap({
                                    centers: {{ Js::from($centersFrontEndData) }},
                                    regions: {{ Js::from($centersByRegion) }},
                                    mapTileLayer: {{ Js::from(config('services.map.tileLayer')) }},
                                    mapTileLayerAttribution:
                                        {{ Js::from(config('services.map.tileLayerAttribution')) }},
                                })"
                        x-on:leaflet-loaded.window.camel="initMap()"
                        x-init="init()"
                        class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_2fr]"
                    >
                        <select
                            x-model="selectedKey"
                            @change="selectCenter($event.target.value)"
                                   vent.target.value)"
                                   ct"
                                    border border-warm-200 bg-white px-3 py-2 text-sm font-medium text-warm-800 sm:hidden dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                                   中心</option>
                                   0zu8NQB"
                                   gion.label">
                                   aLk5r4M5GlFOiXYU8Tkj6B"
                                   .key"
                                   center.key"
                                   BdjNlaub7SbseB"
                                   min gap-3 overflow-y-auto sm:grid lg:gap-2"
                                   PZ4JibB"
                                   1">
                                   text-xs font-semibold tracking-wide text-warm-500 uppercase dark:text-zinc-500"
                                   VMVwYOQEhYB"
                                   grid-cols-3 gap-1 lg:grid-cols-1 lg:gap-0.5"
                                   TurPHhY3jqoSizueMRxBa6AV5B"
                                   nter.key"
                                   ="button"
                                   ck="selectCenter(center.key)"
                                   ss="
                                       selectedKey === center.key
                                           ? 'bg-warm-800 text-white dark:bg-zinc-100 dark:text-zinc-900'
                                           : 'text-warm-700 hover:bg-warm-100 dark:text-zinc-300 dark:hover:bg-zinc-800'
                                   "
                                   vent.target.value)"
                                   ct"
                                    border border-warm-200 bg-white px-3 py-2 text-sm font-medium text-warm-800 sm:hidden dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                                   中心</option>
                                   0zu8NQB"
                                   gion.label">
                                   aLk5r4M5GlFOiXYU8Tkj6B"
                                   .key"
                                   center.key"
                                   BdjNlaub7SbseB"
                                   min gap-3 overflow-y-auto sm:grid lg:gap-2"
                                   PZ4JibB"
                                   1">
                                   text-xs font-semibold tracking-wide text-warm-500 uppercase dark:text-zinc-500"
                                   VMVwYOQEhYB"
                                   grid-cols-3 gap-1 lg:grid-cols-1 lg:gap-0.5"
                                   TurPHhY3jqoSizueMRxBa6AV5B"
                                   nter.key"
                                   ="button"
                                   ck="selectCenter(center.key)"
                                   ss="
                                       selectedKey === center.key
                                           ? 'bg-warm-800 text-white dark:bg-zinc-100 dark:text-zinc-900'
                                           : 'text-warm-700 hover:bg-warm-100 dark:text-zinc-300 dark:hover:bg-zinc-800'
                                   "
                                   s="truncate rounded-lg px-3 py-2 text-left text-sm font-medium transition"
                                   a-testid="'center-button-' + center.key"
                                   xt="center.name"
                                   n>
                                   i3CXUhQOB">
                                   "
                                   ter-details"
                                   ntainer"
                                   "center-map"
                                   8u5xmRJmbpYEGo49M7azGB"
                                   w-full rounded-lg border border-warm-100 dark:border-zinc-800"
                                   t9Mnptno0TmmE1BCY0lQB"
                                   "center-map-offline-notice"
                                   h-80 w-full items-center justify-center rounded-lg border border-warm-100 px-4 text-center text-sm text-warm-700 dark:border-zinc-800 dark:text-zinc-500"
                                   線時已隱藏
                                   -y-2 text-sm text-warm-700 dark:text-zinc-300"
                                   ext-base font-semibold text-warm-900 dark:text-zinc-100"
                                   BWmg281xnFa6340wHYPmDB"
                                   BTWpQ5LlqzVbVcbuMgpBxAoMB"
                                   lex items-start gap-1"
                                   con-o-map-pin
                                   s="mt-0.5 size-4 shrink-0"
                                   ="button"
                                   -testid="center-address-button"
                                   s="text-left text-orange-600 hover:underline"
                                   ck="openMapSelectionModal()"
                                   abled="!selectedCenter.latitude || !selectedCenter.longitude"
                                   ss="
                                       selectedCenter.latitude && selectedCenter.longitude
                                           ? 'cursor-pointer'
                                           : 'cursor-not-allowed opacity-50'
                                   "
                                            <span class="truncate">
                                                交通資訊
                                            </span>
                                            <x-heroicon-o-truck
                                                class="size-4 shrink-0 text-warm-400 dark:text-zinc-500"
                                            />
                                        </a>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!selectedCenter">
                                <div
                                    data-testid="center-placeholder"
                                    class="flex h-80 w-full items-center justify-center rounded-lg border border-warm-100 text-warm-700 md:text-lg dark:border-zinc-800 dark:text-zinc-500"
                                >
                                    <span class="hidden md:inline">
                                        從左側選擇一個學習指導中心來檢視詳情
                                    </span>
                                    <span class="md:hidden">
                                        從上方選擇一個學習指導中心來檢視詳情
                                    </span>
                                </div>
                            </template>
                        </div>

                        <x-map-selection-modal
                            description="選擇你慣用的地圖應用程式來檢視學習指導中心位置。"
                            google-action="openInMap('google', selectedCenter.googleMapsUrl)"
                        />
                    </div>
                </x-card>
            @endif
        </div>

        <script>
            function linksCenterMap(config) {
                return {
                    centers: config.centers,
                    regions: config.regions,
                    mapTileLayer: config.mapTileLayer,
                    mapTileLayerAttribution: config.mapTileLayerAttribution,
                    selectedKey: null,
                    map: null,
                    marker: null,
                    mapInitialized: false,
                    showMapSelectionModal: false,

                    get selectedCenter() {
                        return (
                            this.centers.find(
                                center => center.key === this.selectedKey
                            ) ?? null
                        )
                    },

                    init() {
                        if (window.leaflet) {
                            this.initMap()
                        }
                    },

                    initMap() {
                        if (
                            this.mapInitialized ||
                            !this.$refs.mapContainer ||
                            !window.leaflet ||
                            !this.selectedCenter ||
                            this.$store.network.offline
                        ) {
                            return
                        }

                        this.mapInitialized = true

                        this.map = window.leaflet
                            .map(this.$refs.mapContainer, {
                                zoomControl: true,
                                boxZoom: true,
                                doubleClickZoom: false,
                                dragging: true,
                                keyboard: false,
                                scrollWheelZoom: true,
                                touchZoom: true,
                            })
                            .setView(
                                [
                                    this.selectedCenter.latitude,
                                    this.selectedCenter.longitude,
                                ],
                                16
                            )

                        this.marker = window.leaflet
                            .marker([
                                this.selectedCenter.latitude,
                                this.selectedCenter.longitude,
                            ])
                            .addTo(this.map)

                        this.marker.bindPopup(this.selectedCenter.name)

                        window.leaflet
                            .tileLayer(this.mapTileLayer, {
                                attribution: this.mapTileLayerAttribution,
                            })
                            .addTo(this.map)
                    },

                    selectCenter(key) {
                        this.selectedKey = key

                        if (!this.selectedCenter) {
                            return
                        }

                        const latlng = [
                            this.selectedCenter.latitude,
                            this.selectedCenter.longitude,
                        ]

                        if (!this.mapInitialized) {
                            this.$nextTick(() => this.initMap())
                            return
                        }

                        this.map.setView(latlng, 16)
                        this.marker.setLatLng(latlng)
                        this.marker.bindPopup(this.selectedCenter.name)
                    },

                    openMapSelectionModal() {
                        if (
                            !this.selectedCenter ||
                            !this.selectedCenter.latitude ||
                            !this.selectedCenter.longitude
                        ) {
                            return
                        }
                        this.showMapSelectionModal = true
                    },

                    closeMapSelectionModal() {
                        this.showMapSelectionModal = false
                    },

                    openInMap(mapService, overrideUrl = null) {
                        const lat = this.selectedCenter.latitude
                        const lon = this.selectedCenter.longitude
                        const label = encodeURIComponent(
                            this.selectedCenter.name
                        )

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
                            this.closeMapSelectionModal()
                        }
                    },
                }
            }
        </script>
    </div>
</x-layout>
