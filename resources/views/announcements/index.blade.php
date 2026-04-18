<x-layout title="學校公告 - NOU 小幫手" description="彙整校內公告。">
    <div class="mx-auto max-w-6xl space-y-6">
        <div
            class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
        >
            <div class="space-y-2">
                <h2 class="text-3xl font-bold text-warm-900">學校公告</h2>
            </div>
        </div>

        @php
            $sourceCategoryTree = $sourceCategories->map(fn ($categories): array => $categories->values()->all())->toArray();
            $displaySelectedSourceCategories = collect($selectedSourceCategories)
                ->mapWithKeys(function (array $selectedCategories, string $source) use ($sourceCategoryTree): array {
                    $availableCategories = $sourceCategoryTree[$source] ?? [];
                    $hasSelectedAllCategories = $availableCategories !== [] && count($selectedCategories) === count($availableCategories) && array_diff($availableCategories, $selectedCategories) === [];

                    return [$source => $hasSelectedAllCategories ? [] : $selectedCategories];
                })
                ->all();
            $totalSelectedCategories = collect($selectedSourceCategories)
                ->flatten()
                ->count();
        @endphp

        <x-card title="選擇來源">
            <div
                x-data="{
                    expanded: false,
                    sourceCategories: @js($sourceCategoryTree),
                    selected: @js($selectedSourceCategories),
                    categoriesFor(source) {
                        return this.sourceCategories[source] ?? []
                    },
                    selectedFor(source) {
                        return this.selected[source] ?? []
                    },
                    isCategoryChecked(source, category) {
                        return this.selectedFor(source).includes(category)
                    },
                    isSourceChecked(source) {
                        const total = this.categoriesFor(source).length
                        return total > 0 && this.selectedFor(source).length === total
                    },
                    isSourceIndeterminate(source) {
                        const selectedCount = this.selectedFor(source).length
                        return selectedCount > 0 && ! this.isSourceChecked(source)
                    },
                    toggleSource(source, checked) {
                        if (checked) {
                            this.selected[source] = [...this.categoriesFor(source)]
                            return
                        }

                        delete this.selected[source]
                    },
                    toggleCategory(source, category, checked) {
                        const selectedCategories = [...this.selectedFor(source)]

                        if (checked && ! selectedCategories.includes(category)) {
                            selectedCategories.push(category)
                        }

                        if (! checked) {
                            const index = selectedCategories.indexOf(category)

                            if (index !== -1) {
                                selectedCategories.splice(index, 1)
                            }
                        }

                        if (selectedCategories.length === 0) {
                            delete this.selected[source]
                            return
                        }

                        this.selected[source] = selectedCategories
                    },
                    selectedCategoryCount() {
                        return Object.values(this.selected).reduce(
                            (sum, categories) => sum + categories.length,
                            0,
                        )
                    },
                    selectedSourceCount() {
                        return Object.keys(this.selected).length
                    },
                }"
                class="space-y-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="expanded = !expanded"
                        class="inline-flex w-full flex-col items-center justify-between gap-x-2 rounded-lg border border-warm-200 bg-warm-50 px-4 py-3 text-left text-sm font-medium text-warm-800 transition hover:border-warm-300 hover:bg-warm-100 sm:w-auto sm:min-w-72 sm:flex-row"
                    >
                        <span class="inline-flex items-center gap-2">
                            <x-heroicon-o-adjustments-horizontal
                                class="size-5"
                            />
                            選擇來源
                        </span>
                        <span
                            class="inline-flex items-center gap-1 text-xs text-warm-600"
                        >
                            已選
                            <span x-text="selectedSourceCount()"></span>
                            個來源 /
                            <span x-text="selectedCategoryCount()"></span>
                            個分類
                            <x-heroicon-o-chevron-down
                                class="size-4 transition"
                                x-bind:class="expanded ? 'rotate-180' : ''"
                            />
                        </span>
                    </button>
                </div>

                <form
                    method="GET"
                    action="{{ route('announcements.index') }}"
                    class="space-y-4"
                >
                    <div
                        x-show="expanded"
                        x-transition.opacity.duration.150ms
                        class="grid gap-4"
                    >
                        <div
                            class="max-h-112 space-y-3 overflow-y-auto rounded-xl border border-warm-200 bg-linear-to-br from-white to-warm-50 p-4 sm:p-5"
                        >
                            @foreach ($sourceCategoryTree as $source => $categories)
                                <section
                                    class="rounded-lg border border-warm-200 bg-white/80 p-3 shadow-sm sm:p-4"
                                >
                                    <label
                                        class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 transition hover:bg-warm-50"
                                    >
                                        <input
                                            type="checkbox"
                                            class="size-4 rounded border-warm-300 text-warm-700 focus:ring-warm-300"
                                            :checked="isSourceChecked(@js($source))"
                                            :indeterminate="isSourceIndeterminate(@js($source))"
                                            @change="toggleSource(@js($source), $event.target.checked)"
                                        />
                                        <span
                                            class="text-sm font-semibold text-warm-900"
                                        >
                                            {{ $source }}
                                        </span>
                                    </label>

                                    <div
                                        class="mt-2 grid gap-2 pl-9 sm:grid-cols-2"
                                    >
                                        @foreach ($categories as $category)
                                            <label
                                                class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-warm-700 transition hover:bg-warm-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="source_categories[{{ $source }}][]"
                                                    value="{{ $category }}"
                                                    class="size-4 rounded border-warm-300 text-orange-600 focus:ring-orange-300"
                                                    :checked="isCategoryChecked(@js($source), @js($category))"
                                                    @change="toggleCategory(@js($source), @js($category), $event.target.checked)"
                                                />
                                                <span>{{ $category }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-3"
                        x-show="expanded"
                    >
                        <x-button type="submit" variant="warm-dark">
                            <x-heroicon-o-funnel class="size-4" />
                            套用篩選
                        </x-button>

                        <x-link-button
                            :href="route('announcements.index')"
                            variant="secondary"
                        >
                            清除條件
                        </x-link-button>

                        <button
                            type="button"
                            @click="selected = {}"
                            class="inline-flex items-center gap-2 rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-700 transition hover:border-warm-300 hover:bg-warm-50"
                        >
                            <x-heroicon-o-x-mark class="size-4" />
                            取消目前勾選
                        </button>
                    </div>
                </form>
            </div>

            @if ($selectedSourceCategories !== [])
                <div class="mt-4 space-y-2 text-sm text-warm-600">
                    <span class="font-medium">目前條件：</span>

                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($displaySelectedSourceCategories as $selectedSource => $selectedCategories)
                            <span
                                class="rounded-full bg-warm-100 px-3 py-1 font-medium text-warm-800"
                            >
                                {{ $selectedSource }}
                            </span>

                            @foreach ($selectedCategories as $selectedCategory)
                                <span
                                    class="rounded-full bg-orange-100 px-3 py-1 font-medium text-orange-700"
                                >
                                    {{ $selectedCategory }}
                                </span>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @elseif ($totalSelectedCategories > 0)
                <div class="mt-4 text-sm text-warm-600">
                    目前條件：已勾選 {{ $totalSelectedCategories }} 個分類
                </div>
            @endif
        </x-card>

        <div class="space-y-4">
            @forelse ($announcements as $announcement)
                <article
                    class="rounded-lg border border-warm-200 bg-white p-5 transition hover:border-warm-300"
                >
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="min-w-0 flex-1 space-y-3">
                            <div
                                class="flex flex-wrap items-center gap-2 text-sm"
                            >
                                <span
                                    class="rounded-full bg-warm-100 px-3 py-1 font-medium text-warm-800"
                                >
                                    {{ $announcement->source_name }}
                                </span>

                                <span
                                    class="rounded-full bg-orange-100 px-3 py-1 font-medium text-orange-700"
                                >
                                    {{ $announcement->category }}
                                </span>

                                @if ($announcement->expired_at?->isPast())
                                    <span
                                        class="rounded-full bg-slate-200 px-3 py-1 font-medium text-slate-700"
                                    >
                                        已過期
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <h3
                                    class="flex items-center gap-1 text-xl leading-8 font-semibold text-warm-900"
                                >
                                    @foreach ($announcement->tags ?? [] as $tag)
                                        <span
                                            class="-my-1 truncate rounded border border-warm-200 px-2 py-1 text-sm text-warm-600"
                                        >
                                            {{ $tag }}
                                        </span>
                                    @endforeach

                                    <a
                                        href="{{ $announcement->url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="truncate transition hover:text-orange-700"
                                    >
                                        {{ $announcement->title }}
                                    </a>
                                </h3>
                            </div>
                        </div>

                        <div
                            class="flex shrink-0 flex-col items-start gap-3 lg:items-end"
                        >
                            <p class="text-xs text-warm-500">
                                <span class="sr-only">發布時間：</span>
                                {{ $announcement->published_at?->format('Y/m/d') ?? '未提供' }}
                            </p>
                        </div>
                    </div>
                </article>
            @empty
                <x-card>
                    <div
                        class="flex min-h-56 flex-col items-center justify-center gap-3 text-center"
                    >
                        <x-heroicon-o-inbox class="size-10 text-warm-400" />
                        <div class="space-y-1">
                            <h3 class="text-xl font-semibold text-warm-800">
                                目前沒有符合條件的公告
                            </h3>
                            <p class="text-sm text-warm-500">
                                可以調整來源或分類，或稍後再回來查看。
                            </p>
                        </div>
                    </div>
                </x-card>
            @endforelse
        </div>

        @if ($announcements->hasPages())
            <x-card class="p-4">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-warm-600">
                        第 {{ $announcements->currentPage() }} /
                        {{ $announcements->lastPage() }} 頁，共
                        {{ number_format($announcements->total()) }} 筆結果
                    </p>

                    <div class="flex items-center gap-3">
                        @if ($announcements->onFirstPage())
                            <span
                                class="inline-flex items-center gap-2 rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-400"
                            >
                                <x-heroicon-o-chevron-left class="size-4" />
                                上一頁
                            </span>
                        @else
                            <x-link-button
                                :href="$announcements->previousPageUrl()"
                                variant="secondary"
                            >
                                <x-heroicon-o-chevron-left class="size-4" />
                                上一頁
                            </x-link-button>
                        @endif

                        @if ($announcements->hasMorePages())
                            <x-link-button
                                :href="$announcements->nextPageUrl()"
                                variant="secondary"
                            >
                                下一頁
                                <x-heroicon-o-chevron-right class="size-4" />
                            </x-link-button>
                        @else
                            <span
                                class="inline-flex items-center gap-2 rounded-lg border border-warm-200 px-4 py-2 text-sm text-warm-400"
                            >
                                下一頁
                                <x-heroicon-o-chevron-right class="size-4" />
                            </span>
                        @endif
                    </div>
                </div>
            </x-card>
        @endif
    </div>
</x-layout>
