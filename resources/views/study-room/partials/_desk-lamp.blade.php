{{-- 書桌上的檯燈：計時中亮起。$class 決定大小。 --}}
<svg
    class="{{ $class }} overflow-visible"
    viewBox="0 0 48 48"
    aria-hidden="true"
>
    <circle
        cx="18"
        cy="16"
        r="16"
        class="fill-amber-300/60 transition-opacity duration-700 dark:fill-amber-400/40"
        :class="hasTimer() ? 'opacity-100' : 'opacity-0'"
    />
    {{-- 底座與燈桿 --}}
    <rect x="22" y="40" width="20" height="4" rx="2" class="fill-warm-500 dark:fill-zinc-500" />
    <rect x="30" y="18" width="3" height="23" rx="1.5" class="fill-warm-500 dark:fill-zinc-500" />
    <rect x="22" y="14" width="3" height="12" rx="1.5" transform="rotate(-50 23.5 20)" class="fill-warm-500 dark:fill-zinc-500" />
    {{-- 燈罩 --}}
    <path d="M6 20 L24 8 L34 20 Z" class="fill-warm-600 dark:fill-zinc-400" />
    {{-- 燈泡 --}}
    <circle
        cx="18"
        cy="19"
        r="3"
        class="transition-[fill] duration-700"
        :class="hasTimer()
            ? 'fill-amber-300'
            : 'fill-warm-200 dark:fill-zinc-700'"
    />
</svg>
