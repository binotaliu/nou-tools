@php
    use App\Filament\Resources\WikiPages\WikiPageResource;
@endphp

<div class="space-y-4 text-sm">
    <p class="text-gray-600 dark:text-gray-300">「{{ $plan->previous->title }}」（{{ $plan->previous->term ?? '舊版' }}）→ 「{{ $plan->current->title }}」（{{ $plan->current->term ?? '新版' }}）。 每個受影響的條目會建立一筆修改請求交給 AI 編輯，完成後一律等待審核，不會自動發布。</p>

    @forelse ($plan->affected() as $rolloverPage)
        <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
            <a
                href="{{ WikiPageResource::getUrl('edit', ['record' => $rolloverPage->page]) }}"
                target="_blank"
                class="text-primary-600 dark:text-primary-400 font-medium hover:underline"
                >{{ $rolloverPage->page->currentRevision?->title }}</a
            >
            <span class="text-gray-500">{{ $rolloverPage->page->slug }}</span>

            @unless ($rolloverPage->pageKnown)
                <p class="text-warning-600 dark:text-warning-400 mt-1 text-xs">引用沒有指定頁碼，以下是整份文件有變動的段落。</p>
            @endunless

            <ul
                class="mt-1 list-inside list-disc text-gray-600 dark:text-gray-300"
            >
                @foreach ($rolloverPage->changedSections() as $section)
                    <li>
                        {{ $section['title'] }}
                        <span class="text-gray-500">
                            @if ($section['newKey'] === null)
                                （新文件找不到對應段落）
                            @else
                                （{{ $section['key'] }} → {{ $section['newKey'] }}）
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="text-gray-500">沒有條目引用到有變動的段落。</p>
    @endforelse

    @if (count($plan->unaffected()) > 0)
        <p class="text-gray-500">
            另有 {{ count($plan->unaffected()) }} 個條目只引用沒有變動的段落，不會建立請求： {{ collect($plan->unaffected())->map(fn ($rolloverPage) => $rolloverPage->page->slug)->implode('、') }}
        </p>
    @endif
</div>
