@props([
    'items' => [],
    'hasAnyOverride' => false,
    'scheduleUuid' => null,
])

@php
    // Sort items by their next upcoming class date (earliest first).
    // Items without a future date are pushed to the end.
    $itemsSorted = $items
        ->toCollection()
        ->sortBy(function ($item) {
            $next = $item->courseClass->schedules
                ->filter(fn ($s) => $s->date->isToday() || $s->date->isFuture())
                ->sortBy('date')
                ->first();

            return $next ? $next->date->timestamp : PHP_INT_MAX;
        })
        ->values();
@endphp

<div class="mb-4 overflow-hidden rounded-lg border border-warm-200 bg-white">
    {{-- 桌面版表格 --}}
    <div class="hidden overflow-x-auto md:block print:block">
        <x-schedule-items-table
            :items="$itemsSorted"
            :scheduleUuid="$scheduleUuid"
        />
    </div>

    {{-- 手機版卡片列表 --}}
    <div class="md:hidden print:hidden">
        @forelse ($itemsSorted as $item)
            <div class="border-b border-warm-200 last:border-b-0">
                <x-schedule-item-card
                    :item="$item"
                    class="m-0 rounded-none border-0 border-b border-warm-200"
                />
            </div>
        @empty
            <div class="px-4 py-6 text-center text-warm-600">
                沒有課程。
                <a
                    href="{{ route('schedules.edit', $scheduleUuid) }}"
                    class="font-semibold text-orange-600 hover:underline"
                >
                    點擊編輯課表
                </a>
            </div>
        @endforelse
    </div>

    {{-- 溫馨提示 (時間異動) --}}
    @if ($hasAnyOverride)
        <div
            class="flex items-center gap-1 border-t border-warm-200 bg-warm-50 px-4 py-2 text-xs text-warm-600"
        >
            <x-heroicon-o-exclamation-triangle class="size-4 text-orange-600" />
            <span>表示該次課程時間與一般時間不同</span>
        </div>
    @endif
</div>
