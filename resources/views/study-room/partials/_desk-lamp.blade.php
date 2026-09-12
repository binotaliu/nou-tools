{{--
    書桌上的檯燈：計時中亮起。$class 決定大小。
    燈罩朝左下方照向桌面，燈泡藏在燈罩開口裡，
    亮起時燈罩內側與燈泡一起轉成暖黃色。
--}}
<svg
    class="{{ $class }} overflow-visible"
    viewBox="0 0 48 48"
    aria-hidden="true"
>
    <defs>
        <radialGradient id="study-room-desk-lamp-glow">
            <stop offset="0%" stop-color="#fde68a" stop-opacity="0.8" />
            <stop offset="40%" stop-color="#fcd34d" stop-opacity="0.4" />
            <stop offset="100%" stop-color="#fcd34d" stop-opacity="0" />
        </radialGradient>
    </defs>

    {{-- 燈罩口的光暈。外層只負責在深色模式收斂亮度 --}}
    <g class="dark:opacity-70">
        <g
            class="transition-opacity duration-700"
            :class="hasTimer() ? 'opacity-100' : 'opacity-0'"
        >
            <circle
                cx="12.13"
                cy="22.44"
                r="17"
                fill="url(#study-room-desk-lamp-glow)"
            />
        </g>
    </g>

    <g class="fill-warm-500 dark:fill-zinc-500">
        {{-- 底座：薄圓盤加上中央的小圓丘 --}}
        <path d="M29 41.8 Q35 35.5 41 41.8 Z" />
        <rect x="26" y="41.8" width="18" height="2.8" rx="1.4" />
        {{-- 燈桿與支臂 --}}
        <path
            d="M35 39 L32.5 19 L19 12"
            class="fill-none stroke-warm-500 dark:stroke-zinc-500"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        {{-- 支臂關節 --}}
        <circle cx="32.5" cy="19" r="2.4" />
    </g>

    {{-- 燈罩：從支臂末端往左下張開的喇叭形 --}}
    <path
        d="M16.33 10.24
           L21.67 13.76
           Q21.83 21.35 19.31 27.17
           Q8.83 27.45 4.95 17.71
           Q9.3 13.1 16.33 10.24 Z"
        class="fill-warm-600 dark:fill-zinc-400"
    />

    {{-- 燈罩內側。開口的幾何同時界定燈泡該待的位置 --}}
    <ellipse
        data-testid="study-room-lamp-shade-mouth"
        cx="12.13"
        cy="22.44"
        rx="8.6"
        ry="3"
        transform="rotate(33.4 12.13 22.44)"
        class="transition-[fill] duration-700"
        :class="hasTimer()
            ? 'fill-amber-300'
            : 'fill-warm-800 dark:fill-zinc-600'"
    />

    {{-- 燈泡：藏在燈罩開口裡 --}}
    <circle
        data-testid="study-room-lamp-bulb"
        cx="12.13"
        cy="22.44"
        r="2.1"
        class="transition-[fill] duration-700"
        :class="hasTimer()
            ? 'fill-amber-100'
            : 'fill-warm-200 dark:fill-zinc-300'"
    />

    {{-- 燈罩與支臂之間的轉軸 --}}
    <circle cx="19" cy="12" r="2.2" class="fill-warm-500 dark:fill-zinc-500" />
</svg>
