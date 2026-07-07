<x-card {{ $attributes->merge(['title' => '最新公告']) }}>
    <div class="space-y-1">
        @if (! $hasAnySelection)
            <div
                class="rounded-lg border border-dashed border-warm-300 bg-warm-50 px-4 py-6 text-center text-sm text-warm-600"
            >
                尚未選擇任何公告分類。
                <a
                    href="{{ route('schedules.announcement-preferences', $schedule) }}"
                    class="font-medium text-orange-700 hover:underline"
                >
                    立即選擇
                </a>
            </div>
        @elseif ($announcements->isEmpty())
            <div
                class="rounded-lg border border-dashed border-warm-300 bg-warm-50 px-4 py-6 text-center text-sm text-warm-600"
            >
                目前沒有符合條件的最新公告。
            </div>
        @else
            @foreach ($announcements as $announcement)
                <div
                    class="flex items-start justify-between gap-3 border-b border-warm-100 py-2 last:border-0"
                >
                    <div class="min-w-0 flex-1">
                        <div
                            class="flex flex-wrap items-center gap-1.5 text-xs"
                        >
                            <span
                                class="rounded-full bg-warm-100 px-2 py-0.5 font-medium text-warm-800"
                            >
                                {{ $announcement->source_name }}
                            </span>
                            <span
                                class="rounded-full bg-orange-100 px-2 py-0.5 font-medium text-orange-700"
                            >
                                {{ $announcement->category }}
                            </span>
                        </div>

                        <a
                            href="{{ $announcement->url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-1 line-clamp-1! block max-w-full text-sm font-medium break-all text-warm-900 transition hover:text-orange-700"
                        >
                            {{ $announcement->title }}
                        </a>
                    </div>

                    <p
                        class="shrink-0 text-right text-xs whitespace-nowrap text-warm-500"
                    >
                        @if ($announcement->published_at)
                            @php
                                $relativeLabel = match (true) {
                                    $announcement->published_at->isToday() => '今天',
                                    $announcement->published_at->isYesterday() => '昨天',
                                    $announcement->published_at->isTomorrow() => '明天',
                                    default => $announcement->published_at->diffForHumans(),
                                };
                            @endphp

                            {{ $relativeLabel }}
                            •
                            {{ $announcement->published_at->format('Y/m/d') }}
                        @else
                                未提供
                        @endif
                    </p>
                </div>
            @endforeach
        @endif

        <div
            class="flex flex-col gap-2 pt-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <a
                href="{{ route('announcements.index', ['source_categories' => $selectedSourceCategories]) }}"
                class="text-sm font-medium text-orange-700 hover:underline"
            >
                檢視更多公告
            </a>

            <a
                href="{{ route('schedules.announcement-preferences', $schedule) }}"
                class="text-sm text-warm-600 hover:underline"
            >
                選擇公告分類
            </a>
        </div>
    </div>
</x-card>
