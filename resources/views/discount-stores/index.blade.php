@push('head')
    <x-json-ld
        :data="[
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => '優惠店家',
            'itemListElement' => $viewModel->stores->toCollection()
                ->values()
                ->map(fn ($store, $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('discount-stores.show', $store->id),
                    'name' => $store->name,
                ])
                ->all(),
        ]"
    />
@endpush

<x-layout title="優惠店家 - NOU 小幫手" description="學生優惠店家列表。">
    @php
        $discountStoreFrontEndData = $viewModel->stores->toCollection()->map(
            fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
                'categoryId' => $store->categoryId,
                'type' => $store->type->value,
                'typeLabel' => $store->type->label(),
                'city' => $store->city,
            ],
        );
    @endphp

    <div
        x-data="discountStoreIndex({
                    stores: {{ Js::from($discountStoreFrontEndData) }},
                    initialSearch: {{ Js::from($viewModel->search) }},
                    initialCategory: {{ Js::from($viewModel->selectedCategoryId) }},
                    initialType: {{ Js::from($viewModel->selectedType) }},
                    initialCity: {{ Js::from($viewModel->selectedCity) }},
                })"
        x-cloak
        class="mx-auto max-w-6xl space-y-6"
    >
        <div
            class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
        >
            <div class="space-y-2">
                <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
                    優惠店家
                </h2>
                <p class="text-sm text-warm-600 dark:text-zinc-400">學生優惠店家列表，歡迎回報或新增店家資訊。
                <br />
                此區資料由
                <strong>112姍姍</strong>
                同學維護。</p>
            </div>
            <x-link-button
                :href="route('discount-stores.create')"
                variant="warm-dark"
            >
                <x-heroicon-o-plus class="size-4" />
                新增優惠店家
            </x-link-button>
        </div>

        {{-- Filters --}}
        <x-card>
            <form
                @submit.prevent
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div>
                    <label
                        for="search"
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                    >
                        搜尋
                    </label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        x-model.debounce.500ms="search"
                        placeholder="店家名稱..."
                        @input.debounce.500ms="applyFilters()"
                        class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                    />
                </div>
                <div>
                    <label
                        for="category"
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                    >
                        分類
                    </label>
                    <x-select
                        id="category"
                        name="category"
                        x-model="category"
                        @change="applyFilters()"
                    >
                        <option value="">全部分類</option>
                        @foreach ($viewModel->categories as $category)
                            <option
                                value="{{ $category->id }}"
                                {{ $viewModel->selectedCategoryId == $category->id ? 'selected' : '' }}
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <label
                        for="type"
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                    >
                        類型
                    </label>
                    <x-select
                        id="type"
                        name="type"
                        x-model="type"
                        @change="applyFilters()"
                    >
                        <option value="">全部類型</option>
                        @foreach (\App\Enums\DiscountStoreType::cases() as $storeType)
                            <option
                                value="{{ $storeType->value }}"
                                {{ $viewModel->selectedType === $storeType->value ? 'selected' : '' }}
                            >
                                {{ $storeType->label() }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <label
                        for="city"
                        class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                    >
                        縣市
                    </label>
                    <x-select
                        id="city"
                        name="city"
                        x-model="city"
                        @change="applyFilters()"
                    >
                        <option value="">全部縣市</option>
                        @foreach ($viewModel->cities as $cityName)
                            <option
                                value="{{ $cityName }}"
                                {{ $viewModel->selectedCity === $cityName ? 'selected' : '' }}
                            >
                                {{ $cityName }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                    <x-button type="submit" variant="warm-dark">
                        <x-heroicon-o-funnel class="size-4" />
                        篩選
                    </x-button>
                    <x-link-button
                        href="#"
                        variant="secondary"
                        x-show="hasFilters"
                        @click.prevent="clearFilters()"
                    >
                        清除條件
                    </x-link-button>
                </div>
            </form>
        </x-card>

        {{-- Store List --}}
        <div class="space-y-4">
            @forelse ($viewModel->stores as $store)
                <x-card
                    id="store-{{ $store->id }}"
                    data-index="{{ $loop->index }}"
                    x-show="isStoreVisible($el.dataset.index)"
                >
                    <div class="flex flex-col gap-3">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div
                                class="flex flex-wrap items-center gap-2 text-sm"
                            >
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-warm-100 px-3 py-1 font-medium text-warm-800 dark:bg-zinc-800 dark:text-zinc-200"
                                >
                                    @if ($store->category)
                                        <x-dynamic-component
                                            :component="$store->category->icon"
                                            class="inline-block size-4"
                                        />
                                        {{ $store->category->name }}
                                    @else
                                        未分類
                                    @endif
                                </span>
                                <span
                                    class="rounded-full bg-orange-100 px-3 py-1 font-medium text-orange-700 dark:bg-orange-950/60 dark:text-orange-300"
                                >
                                    {{ $store->type->label() }}
                                </span>

                                @if ($store->city)
                                    <span
                                        class="text-warm-500 dark:text-zinc-400"
                                    >
                                        {{ $store->city }} {{ $store->district }}
                                    </span>
                                @endif
                            </div>

                            <div
                                class="flex flex-col items-start justify-between md:flex-row md:items-center"
                            >
                                <div class="min-w-0 flex-1 flex-col">
                                    <h3
                                        class="truncate text-xl font-semibold text-warm-900 dark:text-zinc-100"
                                    >
                                        <a
                                            href="{{ route('discount-stores.show', $store->id) }}"
                                            class="hover:underline"
                                        >
                                            {{ $store->name }}
                                        </a>
                                    </h3>

                                    <p
                                        class="line-clamp-2 text-sm text-warm-600 dark:text-zinc-400"
                                    >
                                        <a
                                            href="{{ route('discount-stores.show', $store->id) }}"
                                            class="hover:underline"
                                        >
                                            {{ $store->discountDetails }}
                                        </a>
                                    </p>
                                </div>

                                <x-link-button
                                    class="hidden! md:inline-flex!"
                                    :href="route('discount-stores.show', $store->id)"
                                    variant="secondary"
                                >
                                    <x-heroicon-o-eye class="size-4" />
                                    檢視詳情
                                </x-link-button>
                            </div>
                        </div>

                        <div class="mb-4 md:mb-0">
                            @if ($store->latestReportIsValid === null)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800 dark:bg-zinc-800 dark:text-zinc-300"
                                >
                                    <x-heroicon-o-question-mark-circle
                                        class="size-4"
                                    />
                                    尚無回報
                                </span>
                            @elseif ($store->latestReportIsValid)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800 dark:bg-green-950/60 dark:text-green-300"
                                >
                                    <x-heroicon-o-check-circle class="size-4" />
                                    有效 –
                                    <span
                                        title="{{ $store->latestReportCreatedAtDateTime }}"
                                    >
                                        {{ $store->latestReportCreatedAtDate }}
                                    </span>
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800 dark:bg-red-950/60 dark:text-red-300"
                                >
                                    <x-heroicon-o-x-circle class="size-4" />
                                    此優惠似乎無法使用
                                </span>
                            @endif
                        </div>

                        <x-link-button
                            class="flex! md:hidden!"
                            :href="route('discount-stores.show', $store->id)"
                            variant="secondary"
                        >
                            <x-heroicon-o-eye class="size-4" />
                            檢視詳情
                        </x-link-button>
                    </div>
                </x-card>
            @empty
                <x-card>
                    <div
                        class="flex min-h-56 flex-col items-center justify-center gap-3 text-center"
                    >
                        <x-heroicon-o-building-storefront
                            class="size-10 text-warm-400 dark:text-zinc-500"
                        />
                        <div class="space-y-1">
                            <h3
                                class="text-xl font-semibold text-warm-800 dark:text-zinc-200"
                            >
                                目前沒有符合條件的優惠店家
                            </h3>
                            <p class="text-sm text-warm-500 dark:text-zinc-400">可以調整篩選條件，或新增一個優惠店家！</p>
                        </div>
                    </div>
                </x-card>
            @endforelse

            <div
                x-show="filteredStoreIndices.length === 0 && stores.length > 0"
            >
                <x-card>
                    <div
                        class="flex min-h-56 flex-col items-center justify-center gap-3 text-center"
                    >
                        <x-heroicon-o-building-storefront
                            class="size-10 text-warm-400 dark:text-zinc-500"
                        />
                        <div class="space-y-1">
                            <h3
                                class="text-xl font-semibold text-warm-800 dark:text-zinc-200"
                            >
                                目前沒有符合條件的優惠店家
                            </h3>
                            <p class="text-sm text-warm-500 dark:text-zinc-400">可以調整篩選條件，或新增一個優惠店家！</p>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        {{-- Pagination --}}
        <x-card class="p-4" x-show="filteredStoreIndices.length > perPage">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-warm-600 dark:text-zinc-400">第
                <span x-text="page"></span>
                /
                <span x-text="totalPages"></span>
                頁，共
                <span x-text="filteredStoreIndices.length.toLocaleString()"></span>
                筆結果</p>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-400"
                        :disabled="page === 1"
                        @click.prevent="goToPage(page - 1)"
                    >
                        <x-heroicon-o-chevron-left class="size-4" />
                        上一頁
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-400"
                        :disabled="page === totalPages"
                        @click.prevent="goToPage(page + 1)"
                    >
                        下一頁
                        <x-heroicon-o-chevron-right class="size-4" />
                    </button>
                </div>
            </div>
        </x-card>
        <script>
            function discountStoreIndex(config) {
                return {
                    stores: config.stores,
                    search: config.initialSearch ?? '',
                    category: config.initialCategory ?? '',
                    type: config.initialType ?? '',
                    city: config.initialCity ?? '',
                    page: 1,
                    perPage: 20,

                    get normalizedSearch() {
                        return this.search.trim().toLowerCase()
                    },

                    get filteredStoreIndices() {
                        return this.stores.reduce((indexes, store, index) => {
                            const name = store.name.toLowerCase()
                            const matchesSearch =
                                this.normalizedSearch === '' ||
                                name.includes(this.normalizedSearch)
                            const matchesCategory =
                                this.category === '' ||
                                String(store.categoryId) ===
                                    String(this.category)
                            const matchesType =
                                this.type === '' || store.type === this.type
                            const matchesCity =
                                this.city === '' || store.city === this.city

                            if (
                                matchesSearch &&
                                matchesCategory &&
                                matchesType &&
                                matchesCity
                            ) {
                                indexes.push(index)
                            }

                            return indexes
                        }, [])
                    },

                    get visibleStoreIndices() {
                        const pageStart = (this.page - 1) * this.perPage
                        return this.filteredStoreIndices.slice(
                            pageStart,
                            pageStart + this.perPage
                        )
                    },

                    get totalPages() {
                        return Math.max(
                            1,
                            Math.ceil(
                                this.filteredStoreIndices.length / this.perPage
                            )
                        )
                    },

                    get hasFilters() {
                        return (
                            this.search ||
                            this.category ||
                            this.type ||
                            this.city
                        )
                    },

                    applyFilters() {
                        this.page = 1
                    },

                    clearFilters() {
                        this.search = ''
                        this.category = ''
                        this.type = ''
                        this.city = ''
                        this.page = 1
                    },

                    goToPage(page) {
                        this.page = Math.min(this.totalPages, Math.max(1, page))
                    },

                    isStoreVisible(storeIndex) {
                        return this.visibleStoreIndices.includes(
                            Number(storeIndex)
                        )
                    },
                }
            }
        </script>
    </div>
</x-layout>
