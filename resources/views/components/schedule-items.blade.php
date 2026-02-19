@props([
    'items' => [],
    'schedule' => null,
])

@php
    // Sort items by their next upcoming class date (earliest first).
    // Items without a future date are pushed to the end.
    $itemsSorted = collect($items)->sortBy(function ($item) {
        $next = $item->courseClass->schedules
            ->filter(fn($s) => $s->date->isToday() || $s->date->isFuture())
            ->sortBy('date')
            ->first();

        return $next ? $next->date->timestamp : PHP_INT_MAX;
    })->values();

    $hasAnyOverride = collect($items)->contains(function ($item) {
        return $item->courseClass->schedules->contains(function ($s) {
            return $s->start_time !== null;
        });
    });
@endphp

<div class="bg-white rounded-lg border border-warm-200 overflow-hidden mb-4">
    <!-- 桌面版表格 -->
    <div class="hidden md:block print:block overflow-x-auto">
        <x-schedule-items-table :items="$itemsSorted" :schedule="$schedule" />
    </div>

    <!-- 手機版卡片列表 -->
    <div class="md:hidden print:hidden">
        @forelse ($itemsSorted as $item)
            <div class="border-b border-warm-200 last:border-b-0">
                <x-schedule-item-card :item="$item" class="rounded-none m-0 border-0 border-b border-warm-200" />
            </div>
        @empty
            <div class="px-4 py-6 text-center text-warm-600">
                沒有課程。<a href="{{ route('schedule.edit', $schedule) }}"
                           class="text-orange-600 hover:underline font-semibold">點擊編輯課表</a>
            </div>
        @endforelse
    </div>

    <!-- 溫馨提示 (時間異動) -->
    @if ($hasAnyOverride)
        <div class="px-4 py-2 bg-warm-50 border-t border-warm-200 text-xs text-warm-600 flex items-center gap-1">
            <x-heroicon-o-exclamation-triangle class="size-4 text-orange-600" />
            <span>表示該次課程時間與一般時間不同</span>
        </div>
    @endif
</div>
