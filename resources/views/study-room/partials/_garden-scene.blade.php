{{--
    窗外的校園花園：天空、太陽與月亮跟著校園（台灣）的真實日夜變化，
    再往前是遠處的城市、樹籬、草地、樹、長椅、路燈與花叢。
    $class 決定這片景色擺在哪、多大；通常是塞滿一扇窗。
--}}
<div
    class="{{ $class }}"
    role="img"
    :aria-label="gardenAriaLabel()"
    :data-sky-phase="sky.phase"
    :style="gardenVars()"
    data-testid="study-room-garden"
>
    @include('study-room.partials._sky-layers', ['skyLayout' => 'garden'])

    @include('study-room.partials._scene-skyline', ['idPrefix' => 'study-room-garden', 'class' => 'absolute inset-x-0 bottom-[30%] h-[46%] w-full'])

    {{-- 校園圍籬邊的樹籬 --}}
    <svg
        class="absolute inset-x-0 bottom-[27%] h-[10%] w-full"
        viewBox="0 0 1000 100"
        preserveAspectRatio="none"
        aria-hidden="true"
    >
        <path
            d="M0 100 L0 50 C25 10 55 10 80 50 C105 10 135 10 160 50 C185 10 215 10 240 50 C265 10 295 10 320 50 C345 10 375 10 400 50 C425 10 455 10 480 50 C505 10 535 10 560 50 C585 10 615 10 640 50 C665 10 695 10 720 50 C745 10 775 10 800 50 C825 10 855 10 880 50 C905 10 935 10 960 50 C975 25 990 25 1000 50 L1000 100 Z"
            fill="var(--g-hedge)"
        />
    </svg>

    {{-- 草地與小徑 --}}
    <div
        class="absolute inset-x-0 bottom-0 h-[30%] bg-[linear-gradient(to_bottom,var(--g-lawnTop),var(--g-lawnBottom))]"
        aria-hidden="true"
    ></div>
    <svg
        class="absolute inset-x-0 bottom-0 h-[30%] w-full"
        viewBox="0 0 1000 100"
        preserveAspectRatio="none"
        aria-hidden="true"
    >
        <path
            d="M470 0 C480 30 430 55 400 100 L560 100 C520 60 520 30 528 0 Z"
            fill="var(--g-path)"
            opacity="0.85"
        />
    </svg>

    {{-- 前景的樹 --}}
    <svg class="absolute bottom-[16%] left-[4%] h-[34%] w-auto" viewBox="0 0 60 100" aria-hidden="true">
        <rect x="27" y="60" width="6" height="40" rx="2" fill="var(--g-trunk)" />
        <circle cx="30" cy="42" r="26" fill="var(--g-canopyDark)" />
        <circle cx="22" cy="36" r="20" fill="var(--g-canopy)" />
        <circle cx="40" cy="30" r="17" fill="var(--g-canopy)" />
    </svg>
    <svg class="absolute bottom-[20%] left-[17%] h-[28%] w-auto" viewBox="0 0 60 100" aria-hidden="true">
        <rect x="27" y="78" width="6" height="22" rx="2" fill="var(--g-trunk)" />
        <path d="M30 0 L56 44 L42 44 L58 82 L2 82 L18 44 L4 44 Z" fill="var(--g-pine)" />
    </svg>
    <svg class="absolute right-[22%] bottom-[19%] h-[24%] w-auto" viewBox="0 0 60 100" aria-hidden="true">
        <rect x="27" y="70" width="6" height="30" rx="2" fill="var(--g-trunk)" />
        <circle cx="30" cy="48" r="24" fill="var(--g-canopyDark)" />
        <circle cx="24" cy="40" r="18" fill="var(--g-canopy)" />
    </svg>
    <svg class="absolute right-[3%] bottom-[14%] h-[36%] w-auto" viewBox="0 0 60 100" aria-hidden="true">
        <rect x="27" y="58" width="6" height="42" rx="2" fill="var(--g-trunk)" />
        <circle cx="30" cy="40" r="28" fill="var(--g-canopyDark)" />
        <circle cx="38" cy="34" r="20" fill="var(--g-canopy)" />
        <circle cx="18" cy="30" r="15" fill="var(--g-canopy)" />
    </svg>

    {{-- 長椅 --}}
    <svg class="absolute bottom-[10%] left-[30%] h-[9%] w-auto" viewBox="0 0 80 40" aria-hidden="true">
        <rect x="4" y="4" width="72" height="8" rx="2" fill="var(--g-trunk)" />
        <rect x="2" y="18" width="76" height="7" rx="2" fill="var(--g-trunk)" />
        <rect x="8" y="12" width="4" height="28" fill="var(--g-trunk)" />
        <rect x="68" y="12" width="4" height="28" fill="var(--g-trunk)" />
    </svg>

    {{-- 路燈：天黑後亮起 --}}
    <svg class="absolute bottom-[14%] left-[58%] h-[34%] w-auto overflow-visible" viewBox="0 0 40 100" aria-hidden="true">
        <circle cx="20" cy="10" r="26" fill="rgba(255,214,130,0.35)" class="[opacity:var(--g-lamp)]" />
        <rect x="18" y="14" width="4" height="86" rx="1" fill="var(--g-trunk)" />
        <path d="M10 14 L30 14 L26 4 L14 4 Z" fill="var(--g-buildingNear)" />
        <circle cx="20" cy="12" r="5" fill="#ffe08a" class="[opacity:var(--g-lamp)]" />
    </svg>

    {{-- 花叢 --}}
    <div
        class="absolute inset-x-0 bottom-0 h-[30%] opacity-(--g-cloud-opacity)"
        aria-hidden="true"
    >
        <span
            class="absolute bottom-[40%] left-[14%] size-1.5 rounded-full bg-rose-300"
        ></span>
        <span
            class="absolute bottom-[28%] left-[17%] size-1 rounded-full bg-amber-200"
        ></span>
        <span
            class="absolute bottom-[20%] left-[30%] size-1.5 rounded-full bg-yellow-200"
        ></span>
        <span
            class="absolute bottom-[55%] left-[35%] size-1 rounded-full bg-rose-200"
        ></span>
        <span
            class="absolute bottom-[30%] left-[63%] size-1.5 rounded-full bg-fuchsia-300"
        ></span>
        <span
            class="absolute bottom-[50%] left-[68%] size-1 rounded-full bg-amber-200"
        ></span>
        <span
            class="absolute bottom-[18%] left-[80%] size-1.5 rounded-full bg-rose-300"
        ></span>
        <span
            class="absolute bottom-[45%] left-[88%] size-1 rounded-full bg-yellow-200"
        ></span>
    </div>

    {{-- 螢火蟲：天全黑後才出來 --}}
    <div
        class="absolute inset-x-0 bottom-0 h-[45%] opacity-(--g-firefly) transition-opacity duration-1000"
        aria-hidden="true"
    >
        <span
            class="absolute bottom-[30%] left-[12%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)]"
        ></span>
        <span
            class="absolute bottom-[55%] left-[31%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)] [animation-delay:-2.3s]"
        ></span>
        <span
            class="absolute bottom-[40%] left-[52%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)] [animation-delay:-4.1s]"
        ></span>
        <span
            class="absolute bottom-[62%] left-[70%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)] [animation-delay:-1.2s]"
        ></span>
        <span
            class="absolute bottom-[25%] left-[86%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)] [animation-delay:-5.6s]"
        ></span>
    </div>
</div>
