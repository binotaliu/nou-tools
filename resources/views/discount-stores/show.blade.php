@push('head')
    @vite(['resources/js/leaflet.js'])
@endpush

<x-layout
    title="{{ $store->name }} - 優惠店家 - NOU 小幫手"
    description="{{ $store->name }} 的學生優惠與使用資訊。"
    :noindex="true"
>
    <div
        class="mx-auto max-w-4xl space-y-6"
        x-data="discountStoreReportForm({
                    storeName: @js($store->name),
                    hasCoordinates: @js($store->latitude !== null && $store->longitude !== null),
                    latitude: @js((float) $store->latitude),
                    longitude: @js((float) $store->longitude),
                    address: @js($store->address),
                    shouldShowMap: @js($store->type !== \App\Enums\DiscountStoreType::Online && filled($store->address) && $store->latitude !== null && $store->longitude !== null),
                    mapTileLayer: @js(config('services.map.tileLayer')),
                    mapTileLayerAttribution: @js(config('services.map.tileLayerAttribution')),
                    mapMarkerIconUrl: @js(asset('favicon.svg')),
                })"
        x-on:leaflet-loaded.window.camel="
            if (shouldShowMap) {
                initMap()
            }
        "
        x-init="init()"
    >
        <div
            class="flex flex-col flex-wrap items-start justify-center gap-2 text-sm"
        >
            <h2 class="text-3xl font-bold text-warm-900">優惠店家詳情</h2>
            <a
                href="{{ route('discount-stores.index') }}"
                class="inline-flex items-center gap-1 text-warm-600 transition hover:text-warm-900 hover:underline"
            >
                <x-heroicon-o-chevron-left class="size-4" />
                回到優惠店家列表
            </a>
        </div>

        <x-card>
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span
                        class="rounded-full bg-warm-100 px-3 py-1 font-medium text-warm-800"
                    >
                        @if ($store->category)
                            <x-dynamic-component
                                :component="$store->category->icon"
                                class="inline-block size-4"
                            />
                        @endif

                        {{ $store->category?->name ?? '未分類' }}
                    </span>
                    <span
                        class="rounded-full bg-orange-100 px-3 py-1 font-medium text-orange-700"
                    >
                        {{ $store->type->label() }}
                    </span>

                    @if ($store->city)
                        <span class="text-warm-500">
                            {{ $store->city }}
                            {{ $store->district }}
                        </span>
                    @endif
                </div>

                <h2 class="text-3xl font-bold text-warm-900">
                    {{ $store->name }}
                </h2>

                @if ($store->address)
                    <p class="flex items-center gap-1 text-sm text-warm-600">
                        @if ($store->type === \App\Enums\DiscountStoreType::Online)
                            @if (Str::startsWith($store->address, ['http://', 'https://']))
                                <x-heroicon-o-globe-alt
                                    class="inline-block size-4"
                                />
                                <a
                                    href="{{ $store->address }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-orange-600 hover:underline"
                                >
                                    {{ $store->address }}
                                </a>
                            @endif
                        @else
                            <x-heroicon-o-map-pin class="inline-block size-4" />
                            <button
                                type="button"
                                class="text-orange-600 hover:underline"
                                @click="openMapSelectionModal()"
                                :disabled="!hasCoordinates"
                                :class="hasCoordinates ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'"
                            >
                                {{ $store->address }}
                            </button>
                        @endif
                    </p>
                @endif

                @if ($store->type !== \App\Enums\DiscountStoreType::Online && filled($store->address) && $store->latitude !== null && $store->longitude !== null)
                    <div class="space-y-2">
                        <div
                            x-ref="mapContainer"
                            class="h-80 w-full rounded-lg border border-warm-100"
                        ></div>
                        <p class="text-xs text-warm-500">
                            點擊上方地址以在不同地圖應用開啟此位置。
                        </p>
                    </div>
                @endif

                <div class="text-sm text-warm-700">
                    <p class="wrap-break-word">
                        <span class="font-medium">優惠內容：</span>
                        <!-- prettier-ignore -->
                        <span class="whitespace-pre-line">{{ $store->discount_details }}</span>
                    </p>

                    @if ($store->verification_method)
                        <p class="mt-1 wrap-break-word">
                            <span class="font-medium">驗證方式：</span>
                            <!-- prettier-ignore -->
                            <span class="whitespace-pre-line">{{ $store->verification_method }}</span>
                        </p>
                    @endif
                </div>

                @if ($store->notes)
                    <p class="text-sm wrap-break-word text-warm-500">
                        備註：
                        <!-- prettier-ignore -->
                        <span class="whitespace-pre-line">{{ $store->notes }}</span>
                    </p>
                @endif

                @php
                    $latestReport = $store->reports->first();
                    $recentReports = $store->reports->skip(1);
                @endphp

                <div
                    class="space-y-2 rounded-lg border border-warm-100 bg-warm-50 px-4 py-3 text-sm text-warm-700"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium text-warm-900">最新回報</p>
                        @if ($latestReport)
                            <span
                                class="{{ $latestReport->is_valid ? 'text-green-700' : 'text-red-600' }} inline-flex items-center gap-1 font-medium"
                            >
                                @if ($latestReport->is_valid)
                                    <x-heroicon-s-check-circle class="size-4" />
                                    有效
                                @else
                                    <x-heroicon-s-x-circle class="size-4" />
                                    無效
                                @endif
                            </span>
                        @endif
                    </div>

                    @if ($latestReport)
                        <div class="space-y-1">
                            <p class="text-sm wrap-break-word text-warm-700">
                                <!-- prettier-ignore -->
                                <span class="whitespace-pre-line">{{ $latestReport->comment ?: '（無補充說明）' }}</span>
                            </p>

                            <p class="text-xs text-warm-500">
                                {{ $latestReport->created_at->diffForHumans() }}
                            </p>
                        </div>

                        @if ($recentReports->isNotEmpty())
                            <details
                                class="rounded-lg border border-warm-200 bg-white px-3 py-2"
                            >
                                <summary
                                    class="cursor-pointer text-sm font-medium text-warm-700"
                                >
                                    展開看更多近期回報（{{ $recentReports->count() }}）
                                </summary>
                                <div class="mt-2 space-y-2">
                                    @foreach ($recentReports as $report)
                                        <div
                                            class="rounded-md border border-warm-100 bg-warm-50 px-3 py-2"
                                        >
                                            <p
                                                class="{{ $report->is_valid ? 'text-green-700' : 'text-red-600' }} inline-flex items-center gap-1 text-sm font-medium"
                                            >
                                                @if ($report->is_valid)
                                                    <x-heroicon-s-check-circle
                                                        class="size-4"
                                                    />
                                                    有效
                                                @else
                                                    <x-heroicon-s-x-circle
                                                        class="size-4"
                                                    />
                                                    無效
                                                @endif
                                            </p>
                                            <p
                                                class="mt-1 text-sm wrap-break-word text-warm-700"
                                            >
                                                <!-- prettier-ignore -->
                                                <span class="whitespace-pre-line">{{ $report->comment ?: '（無補充說明）' }}</span>
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-warm-500"
                                            >
                                                {{ $report->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    @else
                        <p class="text-sm text-warm-600">
                            目前還沒有回報資料。
                        </p>
                    @endif
                </div>

                <div class="flex flex-col gap-2 border-t border-warm-100 pt-3">
                    <p class="text-sm text-warm-600">
                        使用了本優惠嗎？請協助回報優惠的有效性，讓其他同學參考！
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-green-500 px-3 py-1.5 text-sm text-green-600 transition hover:bg-green-50"
                            title="回報有效"
                            @click="openReportModal(true)"
                        >
                            <x-heroicon-o-check-circle class="size-4" />
                            回報有效
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-red-500 px-3 py-1.5 text-sm text-red-500 transition hover:bg-red-50"
                            title="回報無效"
                            @click="openReportModal(false)"
                        >
                            <x-heroicon-o-x-circle class="size-4" />
                            回報無效
                        </button>
                    </div>
                </div>

                <template x-if="showReportModal">
                    <div
                        x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                        @click.self="closeReportModal()"
                    >
                        <div
                            class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
                        >
                            <h3
                                class="mb-4 text-lg font-semibold text-warm-900"
                            >
                                回報「
                                <span x-text="storeName"></span>
                                」
                                <span x-text="isValid ? '有效' : '無效'"></span>
                            </h3>
                            <form
                                action="{{ route('discount-stores.reports.store', $store) }}"
                                method="POST"
                                class="space-y-4"
                            >
                                @csrf
                                <input
                                    type="hidden"
                                    name="is_valid"
                                    :value="isValid ? '1' : '0'"
                                />
                                <div>
                                    <label
                                        for="report-comment-{{ $store->id }}"
                                        class="mb-1 block text-sm font-medium text-warm-700"
                                    >
                                        備註（選填）
                                    </label>
                                    <textarea
                                        id="report-comment-{{ $store->id }}"
                                        name="comment"
                                        rows="2"
                                        class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300"
                                        placeholder="補充說明..."
                                    ></textarea>
                                </div>
                                <div>
                                    <x-cf-turnstile
                                        id="turnstile__store-report"
                                        language="zh-tw"
                                        explicit
                                    />
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition disabled:bg-gray-400"
                                        :class="isValid ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
                                        :disabled="! reportFormTurnstileChallengeExecuted"
                                    >
                                        確認回報
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-700 transition hover:bg-warm-50"
                                        @click="closeReportModal()"
                                    >
                                        取消
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </x-card>

        <template x-if="showMapSelectionModal">
            <div
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="closeMapSelectionModal()"
            >
                <div
                    class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
                >
                    <h3 class="mb-4 text-lg font-semibold text-warm-900">
                        選擇地圖應用
                    </h3>
                    <p class="mb-6 text-sm text-warm-600">
                        在哪個地圖應用中開啟此位置？
                    </p>
                    <div class="space-y-2">
                        <button
                            type="button"
                            @click="openInMap('osm')"
                            class="w-full rounded-lg border border-warm-200 px-4 py-3 text-left text-sm font-medium text-warm-700 transition hover:bg-warm-50"
                        >
                            <span class="flex items-center gap-2">
                                <x-heroicon-o-globe-alt class="size-4" />
                                在 OpenStreetMap 開啟
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="openInMap('apple')"
                            class="w-full rounded-lg border border-warm-200 px-4 py-3 text-left text-sm font-medium text-warm-700 transition hover:bg-warm-50"
                        >
                            <span class="flex items-center gap-2">
                                <svg
                                    class="size-4"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M18.71 19.71a6 6 0 0 1-8.485 0l-.88-.89a.996.996 0 1 0-1.41 1.41l.89.89a8 8 0 0 0 11.31 0l-.889-.889a.996.996 0 0 0-1.41 1.41l.88.889zM12.005 5.999a5.991 5.991 0 0 0-4.241 10.247l.884.883a.996.996 0 1 0 1.41-1.41l-.883-.884a4 4 0 1 1 5.657 0l-.884.884a.996.996 0 1 0 1.41 1.41l.883-.883A5.991 5.991 0 0 0 12.005 5.999z"
                                    ></path>
                                </svg>
                                在 Apple 地圖開啟
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="openInMap('google')"
                            class="w-full rounded-lg border border-warm-200 px-4 py-3 text-left text-sm font-medium text-warm-700 transition hover:bg-warm-50"
                        >
                            <span class="flex items-center gap-2">
                                <x-heroicon-o-map class="size-4" />
                                在 Google 地圖開啟
                            </span>
                        </button>
                    </div>
                    <button
                        type="button"
                        class="mt-4 w-full rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-700 transition hover:bg-warm-50"
                        @click="closeMapSelectionModal()"
                    >
                        關閉
                    </button>
                </div>
            </div>
        </template>

        @if ($store->comments->isNotEmpty())
            <x-card>
                <div class="space-y-2">
                    <h3
                        class="flex items-center gap-1 text-base font-semibold text-warm-700"
                    >
                        留言
                        @if ($store->comments_count > 0)
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-warm-100 px-2 py-0.5 text-xs font-medium text-warm-800"
                            >
                                {{ $store->comments_count }}
                            </span>
                        @endif
                    </h3>
                    @foreach ($store->comments as $comment)
                        <div
                            class="rounded-lg bg-warm-50 px-3 py-2 text-sm text-warm-700"
                        >
                            <p class="mb-2 text-sm font-medium text-warm-900">
                                {{ $comment->nickname }}
                            </p>
                            <!-- prettier-ignore -->
                            <p class="wrap-break-word whitespace-pre-line">{{ $comment->content }}</p>
                            <span class="text-xs text-warm-400">
                                —
                                {{ $comment->created_at->diffForHumans() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        <x-card>
            <div class="space-y-2">
                <h3 class="text-base font-semibold text-warm-700">新增留言</h3>
                <p class="text-sm text-warm-600">
                    歡迎分享使用經驗，留言會在審核後顯示。
                </p>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-warm-800 px-3 py-2 text-sm font-medium text-white transition hover:bg-warm-900"
                    @click="openCommentModal()"
                >
                    <x-heroicon-o-chat-bubble-left class="size-4" />
                    新增留言
                </button>
            </div>
        </x-card>

        <template x-if="showCommentModal">
            <div
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="closeCommentModal()"
            >
                <div
                    class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
                >
                    <h3 class="mb-4 text-lg font-semibold text-warm-900">
                        新增留言
                    </h3>
                    <form
                        action="{{ route('discount-stores.comments.store', $store) }}"
                        method="POST"
                        class="space-y-4"
                    >
                        @csrf
                        <div class="flex flex-col gap-2">
                            <input
                                type="text"
                                name="nickname"
                                class="rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300"
                                placeholder="暱稱"
                                maxlength="100"
                                required
                                value="{{ old('nickname') }}"
                            />
                            <textarea
                                type="text"
                                name="content"
                                class="flex-1 rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300"
                                placeholder="留言（審核後顯示）..."
                                maxlength="1000"
                                rows="5"
                                required
                            >
{{ old('content') }}</textarea
                            >
                        </div>
                        <div>
                            <x-cf-turnstile
                                id="turnstile__store-comment"
                                language="zh-tw"
                                explicit
                            />
                        </div>
                        <p class="text-xs text-warm-500">
                            為避免垃圾留言，留言將由管理員審核後才會顯示出來。
                        </p>
                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-1 rounded-lg bg-warm-800 px-3 py-2 text-sm font-medium text-white transition hover:bg-warm-900 disabled:bg-gray-400"
                                :disabled="! commentFormTurnstileChallengeExecuted"
                            >
                                <x-heroicon-o-chat-bubble-left class="size-4" />
                                送出
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-700 transition hover:bg-warm-50"
                                @click="closeCommentModal()"
                            >
                                取消
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <script>
            function discountStoreReportForm(config) {
                return {
                    storeName: config.storeName,
                    hasCoordinates: config.hasCoordinates,
                    latitude: config.latitude,
                    longitude: config.longitude,
                    address: config.address,
                    shouldShowMap: config.shouldShowMap,
                    mapTileLayer: config.mapTileLayer,
                    mapTileLayerAttribution: config.mapTileLayerAttribution,
                    mapMarkerIconUrl: config.mapMarkerIconUrl,
                    showReportModal: false,
                    showCommentModal: false,
                    showMapSelectionModal: false,
                    isValid: true,
                    map: null,
                    marker: null,
                    mapInitialized: false,
                    reportTurnstileWidgetId: null,
                    commentTurnstileWidgetId: null,
                    reportFormTurnstileChallengeExecuted: false,
                    commentFormTurnstileChallengeExecuted: false,

                    init() {
                        if (@js((bool) old('nickname') || (bool) old('content'))) {
                            this.openCommentModal()
                        }

                        if (this.shouldShowMap) {
                            this.$nextTick(() => {
                                this.initMap()
                            })
                        }
                    },

                    async initMap() {
                        if (
                            this.mapInitialized ||
                            !this.shouldShowMap ||
                            !this.$refs.mapContainer ||
                            !window.leaflet
                        ) {
                            console.warn('地圖初始化失敗：', {
                                mapInitialized: this.mapInitialized,
                                shouldShowMap: this.shouldShowMap,
                                mapContainerExists: !!this.$refs.mapContainer,
                                leafletLoaded: !!window.leaflet,
                            })
                            return
                        }

                        this.mapInitialized = true

                        const markerIcon = window.leaflet.icon({
                            iconUrl: this.mapMarkerIconUrl,
                            iconSize: [32, 32],
                            iconAnchor: [16, 32],
                            popupAnchor: [0, -32],
                        })

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
                            .setView([this.latitude, this.longitude], 16)

                        this.marker = window.leaflet
                            .marker([this.latitude, this.longitude])
                            .addTo(this.map)

                        this.marker.bindPopup(this.storeName)

                        window.leaflet
                            .tileLayer(this.mapTileLayer, {
                                attribution: this.mapTileLayerAttribution,
                            })
                            .addTo(this.map)
                    },

                    openMapSelectionModal() {
                        if (!this.hasCoordinates) {
                            return
                        }
                        this.showMapSelectionModal = true
                    },

                    closeMapSelectionModal() {
                        this.showMapSelectionModal = false
                    },

                    openInMap(mapService) {
                        const lat = this.latitude
                        const lon = this.longitude
                        const label = encodeURIComponent(this.storeName)

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
                            this.closeMapSelectionModal()
                        }
                    },

                    openReportModal(isValid) {
                        this.isValid = isValid
                        this.showReportModal = true
                        this.$nextTick(() => {
                            this.renderReportTurnstile()
                        })
                    },

                    closeReportModal() {
                        this.showReportModal = false
                        this.reportFormTurnstileChallengeExecuted = false

                        if (
                            window.turnstile &&
                            this.reportTurnstileWidgetId !== null
                        ) {
                            window.turnstile.remove(
                                this.reportTurnstileWidgetId
                            )
                            this.reportTurnstileWidgetId = null
                        }
                    },

                    openCommentModal() {
                        this.showCommentModal = true
                        this.$nextTick(() => {
                            this.renderCommentTurnstile()
                        })
                    },

                    closeCommentModal() {
                        this.showCommentModal = false
                        this.commentFormTurnstileChallengeExecuted = false

                        if (
                            window.turnstile &&
                            this.commentTurnstileWidgetId !== null
                        ) {
                            window.turnstile.remove(
                                this.commentTurnstileWidgetId
                            )
                            this.commentTurnstileWidgetId = null
                        }
                    },

                    renderReportTurnstile() {
                        this.renderTurnstile(
                            'turnstile__store-report',
                            widgetId => {
                                this.reportTurnstileWidgetId = widgetId
                            },
                            () => {
                                this.reportFormTurnstileChallengeExecuted = true
                            },
                            () => {
                                this.reportFormTurnstileChallengeExecuted = false
                            },
                            () => this.showReportModal
                        )
                    },

                    renderCommentTurnstile() {
                        this.renderTurnstile(
                            'turnstile__store-comment',
                            widgetId => {
                                this.commentTurnstileWidgetId = widgetId
                            },
                            () => {
                                this.commentFormTurnstileChallengeExecuted = true
                            },
                            () => {
                                this.commentFormTurnstileChallengeExecuted = false
                            },
                            () => this.showCommentModal
                        )
                    },

                    renderTurnstile(
                        containerId,
                        onRendered,
                        onSuccess,
                        onInvalid,
                        shouldRender
                    ) {
                        const container = document.getElementById(containerId)
                        if (!container) {
                            return
                        }

                        const tryRender = () => {
                            if (!shouldRender()) {
                                return
                            }

                            if (!window.turnstile) {
                                setTimeout(tryRender, 100)
                                return
                            }

                            const widgetId = window.turnstile.render(
                                `#${containerId}`,
                                {
                                    sitekey: container.dataset.sitekey,
                                    theme: container.dataset.theme,
                                    language: container.dataset.language,
                                    size: container.dataset.size,
                                    callback: () => {
                                        onSuccess()
                                    },
                                    'error-callback': () => {
                                        onInvalid()
                                    },
                                    'expired-callback': () => {
                                        onInvalid()
                                    },
                                }
                            )

                            onRendered(widgetId)
                        }

                        tryRender()
                    },
                }
            }
        </script>
    </div>
</x-layout>
