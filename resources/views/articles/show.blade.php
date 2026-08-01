@push('head')
    <x-json-ld
        :data="[
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $viewModel->article->title,
            'description' => $viewModel->article->description,
            'author' => [
                '@type' => 'Organization',
                'name' => $viewModel->article->author,
            ],
            'datePublished' => $viewModel->article->publishedAt->toIso8601String(),
            'dateModified' => ($viewModel->article->updatedAt ?? $viewModel->article->publishedAt)->toIso8601String(),
            'mainEntityOfPage' => url()->current(),
        ]"
    />
@endpush

<x-layout
    :title="$viewModel->article->title . ' - ' . $viewModel->article->type->label() . ' - NOU 小幫手'"
>
    <div class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-6 md:flex-row">
            {{-- Sidebar --}}
            <aside class="shrink-0 md:w-64">
                <div
                    class="sticky top-[6.45rem] rounded-lg border border-warm-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <h3
                        class="mb-3 font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        {{ $viewModel->article->type->label() }}
                    </h3>

                    @if ($viewModel->sidebarContent)
                        <nav
                            class="prose prose-sm max-w-none prose-warm dark:prose-invert"
                        >
                            {{ $viewModel->sidebarContent }}
                        </nav>
                    @endif

                    <div
                        class="mt-4 border-t border-warm-200 pt-4 dark:border-zinc-700"
                    >
                        <a
                            href="{{ route('articles.index', ['type' => $viewModel->article->type->value]) }}"
                            class="inline-flex items-center gap-1 text-sm text-warm-600 transition-colors hover:text-warm-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                        >
                            <x-heroicon-o-chevron-left class="size-3" />
                            回到{{ $viewModel->article->type->label() }}首頁
                        </a>
                    </div>
                </div>
            </aside>

            {{-- Main Content --}}
            <main class="min-w-0 flex-1">
                <article
                    class="rounded-lg border border-warm-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    {{-- Article Header --}}
                    <header
                        class="mb-6 border-b border-warm-200 pb-6 dark:border-zinc-700"
                        x-data="nouArticleShare({
                            shareTitle: {{ Js::from($viewModel->article->title) }},
                            shareUrl: {{ Js::from(url()->current()) }},
                        })"
                    >
                        <div
                            class="mb-3 flex items-start justify-between gap-4"
                        >
                            <h1
                                class="text-3xl font-bold text-warm-900 dark:text-zinc-100"
                            >
                                {{ $viewModel->article->title }}
                            </h1>

                            <x-button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="share()"
                                class="shrink-0"
                                data-testid="article-share-button"
                            >
                                <x-heroicon-o-share class="size-4" />
                                分享
                            </x-button>
                        </div>

                        <div
                            class="flex items-center gap-4 text-sm text-warm-500 dark:text-zinc-400"
                        >
                            <span>
                                作者：{{ $viewModel->article->author }}
                            </span>
                            <span>
                                發表於：{{ $viewModel->article->publishedAt->format('Y 年 m 月 d 日') }}
                            </span>
                            @if ($viewModel->article->updatedAt)
                                <span>
                                    更新於：{{ $viewModel->article->updatedAt->format('Y 年 m 月 d 日') }}
                                </span>
                            @endif
                        </div>

                        <x-modal
                            name="showShareModal"
                            title="分享這篇文章"
                            data-testid="article-share-modal"
                        >
                            <div
                                class="flex items-stretch gap-3 rounded border border-warm-300 bg-white text-sm text-warm-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400"
                            >
                                <input
                                    class="flex-1 px-3 py-2 font-mono break-all text-warm-600 dark:text-zinc-400"
                                    :value="shareUrl"
                                    readonly
                                    @click="$event.target.select()"
                                    x-ref="shareInput"
                                    aria-label="文章連結"
                                />

                                <x-button
                                    type="button"
                                    variant="warm-subtle"
                                    size="sm"
                                    @click="copy()"
                                    x-bind:aria-pressed="copied.toString()"
                                    class="my-1 mr-1 shrink-0 whitespace-nowrap"
                                    data-testid="article-share-copy"
                                >
                                    <span x-show="!copied">
                                        <x-heroicon-o-clipboard-document
                                            class="inline size-4"
                                        />
                                        複製連結
                                    </span>
                                    <span x-show="copied">
                                        <x-heroicon-o-check
                                            class="inline size-4"
                                        />
                                        已複製！
                                    </span>
                                </x-button>
                            </div>

                            <x-slot:footer>
                                <x-button
                                    type="button"
                                    variant="secondary"
                                    @click="showShareModal = false"
                                    data-testid="article-share-close"
                                >
                                    關閉
                                </x-button>
                            </x-slot:footer>
                        </x-modal>
                    </header>

                    {{-- Article Content --}}
                    <div
                        class="prose max-w-none prose-warm dark:prose-zinc dark:prose-invert"
                    >
                        {{ $viewModel->article->content }}
                    </div>

                    {{-- License Footer --}}
                    <footer
                        class="mt-8 border-t border-warm-200 pt-6 dark:border-zinc-700"
                    >
                        <div
                            class="flex items-center gap-3 text-sm text-warm-600 dark:text-zinc-400"
                        >
                            <x-heroicon-o-information-circle
                                class="size-5 shrink-0"
                            />

                            <div>
                                <p
                                    class="sr-only font-medium text-warm-700 dark:text-zinc-300"
                                >授權方式</p>
                                <p>本文採用
                                <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hant" target="_blank" rel="noopener noreferrer" class="text-warm-700 underline transition hover:text-warm-900 hover:no-underline dark:text-zinc-300 dark:hover:text-zinc-100"> 創用 CC 姓名標示─非商業性─相同方式分享 4.0 國際版授權條款 (CC BY-NC-SA 4.0) </a>
                                釋出。</p>
                            </div>
                        </div>
                    </footer>
                </article>
            </main>
        </div>
    </div>
</x-layout>
