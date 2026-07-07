<x-layout
    :title="'公告分類設定 - ' . ($viewModel->schedule->name ?: '我的課表') . ' - NOU 小幫手'"
    :noindex="true"
>
    <div class="mx-auto max-w-4xl">
        <div
            class="mb-8 flex flex-col items-start justify-between gap-3 sm:flex-row"
        >
            <div>
                <h2 class="text-3xl font-bold text-warm-900">公告分類設定</h2>
                <p class="mt-2 text-sm text-warm-600">
                    選擇要在課表頁顯示的公告分類。未選擇任何分類時，課表頁的公告區塊將不顯示任何公告。
                </p>
            </div>

            <x-link-button
                :href="route('schedules.show', $viewModel->schedule)"
                variant="secondary"
                class="w-full sm:w-auto"
            >
                <x-heroicon-o-arrow-left class="size-4" />
                返回課表
            </x-link-button>
        </div>

        @php
            $groupLabels = [
                \App\Enums\AnnouncementSourceGroup::Administrative->value => '各處室',
                \App\Enums\AnnouncementSourceGroup::Center->value => '學習指導中心',
                \App\Enums\AnnouncementSourceGroup::Department->value => '學系',
            ];

            $groupedCatalogTree = $viewModel->groupedCatalog
                ->map(fn ($sources) => $sources->map(fn ($categories) => $categories->values()->all())->toArray())
                ->toArray();

            $flatCatalogTree = collect($groupedCatalogTree)
                ->flatMap(fn ($sources) => $sources)
                ->toArray();
        @endphp

        <form
            method="POST"
            action="{{ route('schedules.announcement-preferences.update', $viewModel->schedule) }}"
            x-data="{
                catalog: @js($groupedCatalogTree),
                flatCatalog: @js($flatCatalogTree),
                selected: @js($viewModel->selectedSourceCategories),
                openGroups: {},
                openSources: {},
                sourcesFor(group) {
                    return Object.keys(this.catalog[group] ?? {})
                },
                categoriesFor(source) {
                    return this.flatCatalog[source] ?? []
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
                isGroupChecked(group) {
                    const sources = this.sourcesFor(group)
                    return (
                        sources.length > 0 &&
                        sources.every(source => this.isSourceChecked(source))
                    )
                },
                isGroupIndeterminate(group) {
                    if (this.isGroupChecked(group)) {
                        return false
                    }

                    return this.sourcesFor(group).some(
                        source => this.selectedFor(source).length > 0,
                    )
                },
                isSourceExpanded(source) {
                    return this.openSources[source] ?? false
                },
                toggleSourceExpansion(source) {
                    this.openSources[source] = ! this.isSourceExpanded(source)
                },
                isGroupExpanded(group) {
                    return this.openGroups[group] ?? true
                },
                toggleGroupExpansion(group) {
                    this.openGroups[group] = ! this.isGroupExpanded(group)
                },
                toggleSource(source, checked) {
                    if (checked) {
                        this.selected[source] = [...this.categoriesFor(source)]
                        return
                    }

                    delete this.selected[source]
                },
                toggleGroup(group, checked) {
                    this.sourcesFor(group).forEach(source =>
                        this.toggleSource(source, checked),
                    )
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
            }"
            class="space-y-6"
        >
            @csrf
            @method('PUT')

            @foreach ($groupLabels as $groupValue => $groupLabel)
                <x-card :title="$groupLabel">
                    <div class="space-y-2">
                        <div
                            class="flex items-center justify-between gap-2 rounded-lg border border-warm-200 bg-warm-50 px-2"
                        >
                            <label
                                class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 rounded-md px-2 py-2"
                            >
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-warm-300 text-warm-700 focus:ring-warm-300"
                                    :checked="isGroupChecked(@js($groupValue))"
                                    :indeterminate="isGroupIndeterminate(@js($groupValue))"
                                    @change="toggleGroup(@js($groupValue), $event.target.checked)"
                                />
                                <span
                                    class="min-w-0 truncate text-sm font-semibold text-warm-900"
                                >
                                    {{ $groupLabel }}（全選）
                                </span>
                            </label>

                            <button
                                type="button"
                                @click="toggleGroupExpansion(@js($groupValue))"
                                class="inline-flex items-center rounded-md p-2 text-warm-600 transition hover:bg-warm-100 hover:text-warm-800"
                                :aria-expanded="isGroupExpanded(@js($groupValue))"
                                aria-label="展開或收合 {{ $groupLabel }} 分類"
                            >
                                <span
                                    class="inline-flex transition"
                                    x-bind:class="isGroupExpanded(@js($groupValue)) ? 'rotate-180' : ''"
                                >
                                    <x-heroicon-o-chevron-down class="size-4" />
                                </span>
                            </button>
                        </div>

                        <div
                            x-show="isGroupExpanded(@js($groupValue))"
                            x-transition.opacity.duration.150ms
                            class="space-y-2 pl-2"
                        >
                            @foreach ($groupedCatalogTree[$groupValue] ?? [] as $source => $categories)
                                <section
                                    class="overflow-hidden rounded-lg border border-warm-200 p-1"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <label
                                            class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 rounded-md px-2 py-2 transition hover:bg-warm-50"
                                        >
                                            <input
                                                type="checkbox"
                                                class="size-4 rounded border-warm-300 text-warm-700 focus:ring-warm-300"
                                                :checked="isSourceChecked(@js($source))"
                                                :indeterminate="isSourceIndeterminate(@js($source))"
                                                @change="toggleSource(@js($source), $event.target.checked)"
                                            />
                                            <span
                                                class="min-w-0 truncate text-sm font-semibold text-warm-900"
                                            >
                                                {{ $source }}
                                            </span>
                                        </label>

                                        @if (count($categories) > 0)
                                            <button
                                                type="button"
                                                @click="toggleSourceExpansion(@js($source))"
                                                class="inline-flex items-center rounded-md p-2 text-warm-600 transition hover:bg-warm-100 hover:text-warm-800"
                                                :aria-expanded="isSourceExpanded(@js($source))"
                                                aria-label="展開或收合 {{ $source }} 分類"
                                            >
                                                <span
                                                    class="inline-flex transition"
                                                    x-bind:class="isSourceExpanded(@js($source)) ? 'rotate-180' : ''"
                                                >
                                                    <x-heroicon-o-chevron-down
                                                        class="size-4"
                                                    />
                                                </span>
                                            </button>
                                        @endif
                                    </div>

                                    <div
                                        x-show="isSourceExpanded(@js($source))"
                                        x-transition.opacity.duration.150ms
                                        class="mt-2 grid gap-2 pr-2 pl-9"
                                    >
                                        @foreach ($categories as $category)
                                            <label
                                                class="flex min-w-0 cursor-pointer items-start gap-2 rounded-md px-2 py-1.5 text-sm text-warm-700 transition hover:bg-warm-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="announcement_categories[{{ $source }}][]"
                                                    value="{{ $category }}"
                                                    class="size-4 rounded border-warm-300 text-orange-600 focus:ring-orange-300"
                                                    :checked="isCategoryChecked(@js($source), @js($category))"
                                                    @change="toggleCategory(@js($source), @js($category), $event.target.checked)"
                                                />
                                                <span class="wrap-break-word">
                                                    {{ $category }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </x-card>
            @endforeach

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-button
                    type="submit"
                    variant="primary"
                    class="w-full sm:w-auto"
                >
                    <x-heroicon-o-check class="size-4" />
                    儲存公告分類設定
                </x-button>

                <x-link-button
                    :href="route('schedules.show', $viewModel->schedule)"
                    variant="secondary"
                    class="w-full sm:w-auto"
                >
                    取消
                </x-link-button>
            </div>
        </form>
    </div>
</x-layout>
