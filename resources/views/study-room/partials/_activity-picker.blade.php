{{--
    活動選擇器（單選按鈕群）：開始計時前的表單、以及計時中「變更活動」對話框共用。
    透過 $verbModel 綁定不同的 Alpine 欄位，並透過 testid 參數避免兩處元素重複 data-testid。
--}}
@php
    $verbModel ??= 'selectedVerb';
    $verbGroupTestid ??= 'study-room-verb-group';
    $verbTestidPrefix ??= 'study-room-verb-';
    $gridColsClass ??= 'grid-cols-5';
@endphp

<div>
    <span
        class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
        >活動</span
    >
    <div
        class="grid {{ $gridColsClass }} gap-2"
        role="radiogroup"
        aria-label="活動"
        data-testid="{{ $verbGroupTestid }}"
    >
        @foreach ($viewModel->verbs as $verb)
            @php
                $verbIcon = match ($verb->value) {
                    'exam_prep' => 'heroicon-o-academic-cap',
                    'reading' => 'heroicon-o-book-open',
                    'homework' => 'heroicon-o-pencil-square',
                    'review' => 'heroicon-o-arrow-path',
                    'in_person_class' => 'heroicon-o-presentation-chart-bar',
                    default => 'heroicon-o-check-circle',
                };
            @endphp
            <label
                class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border border-warm-200 bg-white px-1 py-2.5 text-center transition has-checked:border-warm-700 has-checked:bg-warm-700 has-checked:text-white sm:gap-1.5 sm:px-3 sm:py-3 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:has-checked:border-warm-500 dark:has-checked:bg-warm-500 dark:has-checked:text-zinc-950"
            >
                <input
                    type="radio"
                    value="{{ $verb->value }}"
                    x-model="{{ $verbModel }}"
                    class="sr-only"
                    data-testid="{{ $verbTestidPrefix }}{{ $verb->value }}"
                />
                <x-dynamic-component
                    :component="$verbIcon"
                    class="size-5 sm:size-6"
                />
                <span
                    class="text-[11px] leading-tight font-medium sm:text-sm"
                    >{{ $verb->label }}</span
                >
            </label>
        @endforeach
    </div>
</div>
