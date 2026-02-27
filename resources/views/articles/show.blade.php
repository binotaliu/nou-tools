<x-layout
    :title="$article->title . ' - ' . $article->type->label() . ' - NOU 小幫手'"
>
    <div class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-6 md:flex-row">
            {{-- Sidebar --}}
            <aside class="shrink-0 md:w-64">
                <div
                    class="sticky top-4 rounded-lg border border-warm-200 bg-white p-4 shadow-sm"
                >
                    <h3 class="mb-3 font-semibold text-warm-900">
                        {{ $article->type->label() }}
                    </h3>

                    @if ($sidebarContent)
                        <nav class="prose prose-sm max-w-none prose-warm">
                            {{ $sidebarContent }}
                        </nav>
                    @endif

                    <div class="mt-4 border-t border-warm-200 pt-4">
                        <a
                            href="{{ route('articles.index', ['type' => $article->type->value]) }}"
                            class="inline-flex items-center gap-1 text-sm text-warm-600 transition-colors hover:text-warm-900"
                        >
                            <x-heroicon-o-chevron-left class="size-3" />
                            回到列表
                        </a>
                    </div>
                </div>
            </aside>

            {{-- Main Content --}}
            <main class="min-w-0 flex-1">
                <article
                    class="rounded-lg border border-warm-200 bg-white p-8 shadow-sm"
                >
                    {{-- Article Header --}}
                    <header class="mb-6 border-b border-warm-200 pb-6">
                        <h1 class="mb-3 text-3xl font-bold text-warm-900">
                            {{ $article->title }}
                        </h1>

                        <div
                            class="flex items-center gap-4 text-sm text-warm-500"
                        >
                            <span>作者：{{ $article->author }}</span>
                            <span>
                                發表於：{{ $article->publishedAt->format('Y 年 m 月 d 日') }}
                            </span>
                        </div>
                    </header>

                    {{-- Article Content --}}
                    <div class="prose max-w-none prose-warm">
                        {{ $article->content }}
                    </div>

                    {{-- License Footer --}}
                    <footer class="mt-8 border-t border-warm-200 pt-6">
                        <div
                            class="flex items-center gap-3 text-sm text-warm-600"
                        >
                            <x-heroicon-o-information-circle
                                class="size-5 shrink-0"
                            />

                            <div>
                                <p class="sr-only font-medium text-warm-700">
                                    授權方式
                                </p>
                                <p>
                                    本文採用
                                    <a
                                        href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hant"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-warm-700 underline transition hover:text-warm-900 hover:no-underline"
                                    >
                                        創用 CC 姓名標示─非商業性─相同方式分享
                                        4.0 國際版授權條款 (CC BY-NC-SA 4.0)
                                    </a>
                                    釋出。
                                </p>
                            </div>
                        </div>
                    </footer>
                </article>
            </main>
        </div>
    </div>
</x-layout>
