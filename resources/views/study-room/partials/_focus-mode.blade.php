{{--
    專注模式：把操作面板放大成整個視窗，畫的是你坐在單人座上看出去的樣子——
    眼前是隔板牆，牆上是倒數與進度；隔板上方是窗，窗外是跟花園同一片天；
    底下是你的書桌與檯燈。只在計時中可以打開；計時結束或離開座位時自動關閉。
    只佔滿瀏覽器視窗，不會要求進入瀏覽器的全螢幕。
--}}
<template x-if="focusMode">
    <div
        class="fixed inset-0 z-50 animate-focus-in overflow-hidden select-none"
        :style="carrelVars()"
        :data-sky-phase="sky.phase"
        role="dialog"
        aria-modal="true"
        aria-label="專注模式"
        @keydown.escape.window="closeFocusMode()"
        data-testid="study-room-focus-mode"
    >
        {{-- 隔板牆：跟著窗外的光線變亮變暗 --}}
        <div
            class="absolute inset-0 bg-[linear-gradient(to_bottom,var(--c-wall)_0%,var(--c-wall)_60%,var(--c-wall-deep)_100%)] transition-[background] duration-1000"
            aria-hidden="true"
        ></div>

        {{-- 窗光灑在牆上 --}}
        <div
            class="absolute top-[46%] left-1/2 h-[30%] w-[80%] max-w-5xl -translate-x-1/2 opacity-70 blur-2xl transition-[background] duration-1000"
            :style="windowLightStyle()"
            aria-hidden="true"
        ></div>

        {{-- 檯燈的光暈：照在牆與桌面上 --}}
        <div
            class="pointer-events-none absolute right-[-6%] bottom-[2%] size-[70vmin] rounded-full bg-amber-200 opacity-(--c-lamp) blur-3xl transition-opacity duration-1000"
            style="mix-blend-mode: soft-light"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute right-[-2%] bottom-[6%] size-[36vmin] rounded-full bg-amber-300/60 opacity-(--c-lamp) blur-2xl transition-opacity duration-1000"
            aria-hidden="true"
        ></div>

        {{-- 上方：你是誰、時鐘、離開全螢幕 --}}
        <div
            class="absolute inset-x-0 top-0 z-10 flex items-center justify-between gap-3 px-4 py-3 sm:px-6"
            :style="focusInkStyle()"
        >
            <div class="flex min-w-0 items-center gap-2 text-sm">
                <span class="text-xl leading-none" x-text="profileEmoji"></span>
                <span
                    class="truncate font-medium"
                    x-text="profileNickname"
                ></span>
                <span class="hidden opacity-60 sm:inline">·</span>
                <span
                    class="hidden truncate opacity-60 sm:inline"
                    x-text="mySeatLabel()"
                ></span>
            </div>
            <p class="hidden text-sm font-medium tabular-nums opacity-80 sm:block">
                <span
                    x-text="clockTimeLabel()"
                    data-testid="study-room-focus-clock"
                ></span>
                <span class="mx-1 opacity-60">·</span>
                <span x-text="clockDateLabel()"></span>
            </p>
            <button
                type="button"
                @click="closeFocusMode()"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm backdrop-blur transition"
                :class="focusChromeClass()"
                data-testid="study-room-focus-mode-close"
            >
                <x-heroicon-o-arrows-pointing-in class="size-4" />
                離開全螢幕
                <kbd
                    class="ml-1 hidden rounded border border-current/30 px-1 text-[10px] opacity-70 sm:inline"
                    >Esc</kbd
                >
            </button>
        </div>

        {{-- 窗：厚框、十字窗櫺，窗外是天空與遠處的校園 --}}
        <div
            class="absolute top-[9%] left-1/2 h-[37%] w-[88%] max-w-5xl -translate-x-1/2 rounded-md shadow-[0_12px_40px_rgba(0,0,0,0.25)] transition-[background-color] transition-[background] duration-1000"
            :style="frameStyle()"
            role="img"
            :aria-label="gardenAriaLabel()"
            data-testid="study-room-focus-window"
        >
            <div
                class="absolute inset-[10px] overflow-hidden shadow-[inset_0_0_0_1px_rgba(0,0,0,0.2)] sm:inset-[14px]"
            >
                @include('study-room.partials._sky-layers', ['skyLayout' => 'focus'])

                @include('study-room.partials._scene-skyline', ['idPrefix' => 'study-room-focus', 'class' => 'absolute inset-x-0 bottom-[16%] h-[22%] w-full'])

                <svg
                    class="absolute inset-x-0 bottom-[14%] h-[5%] w-full"
                    viewBox="0 0 1000 100"
                    preserveAspectRatio="none"
                    aria-hidden="true"
                >
                    <path
                        d="M0 100 L0 50 C25 10 55 10 80 50 C105 10 135 10 160 50 C185 10 215 10 240 50 C265 10 295 10 320 50 C345 10 375 10 400 50 C425 10 455 10 480 50 C505 10 535 10 560 50 C585 10 615 10 640 50 C665 10 695 10 720 50 C745 10 775 10 800 50 C825 10 855 10 880 50 C905 10 935 10 960 50 C975 25 990 25 1000 50 L1000 100 Z"
                        fill="var(--g-hedge)"
                    />
                </svg>
                <div
                    class="absolute inset-x-0 bottom-0 h-[16%] bg-[linear-gradient(to_bottom,var(--g-lawnTop),var(--g-lawnBottom))]"
                    aria-hidden="true"
                ></div>

                <svg class="absolute bottom-[8%] left-[3%] h-[36%] w-auto" viewBox="0 0 60 100" aria-hidden="true">
                    <rect x="27" y="60" width="6" height="40" rx="2" fill="var(--g-trunk)" />
                    <circle cx="30" cy="42" r="26" fill="var(--g-canopyDark)" />
                    <circle cx="22" cy="36" r="20" fill="var(--g-canopy)" />
                    <circle cx="40" cy="30" r="17" fill="var(--g-canopy)" />
                </svg>
                <svg class="absolute right-[4%] bottom-[6%] h-[40%] w-auto" viewBox="0 0 60 100" aria-hidden="true">
                    <rect x="27" y="58" width="6" height="42" rx="2" fill="var(--g-trunk)" />
                    <circle cx="30" cy="40" r="28" fill="var(--g-canopyDark)" />
                    <circle cx="38" cy="34" r="20" fill="var(--g-canopy)" />
                    <circle cx="18" cy="30" r="15" fill="var(--g-canopy)" />
                </svg>
                <svg class="absolute bottom-[10%] left-[64%] h-[26%] w-auto overflow-visible" viewBox="0 0 40 100" aria-hidden="true">
                    <circle cx="20" cy="10" r="26" fill="rgba(255,214,130,0.35)" style="opacity: var(--g-lamp)" />
                    <rect x="18" y="14" width="4" height="86" rx="1" fill="var(--g-trunk)" />
                    <path d="M10 14 L30 14 L26 4 L14 4 Z" fill="var(--g-buildingNear)" />
                    <circle cx="20" cy="12" r="5" fill="#ffe08a" style="opacity: var(--g-lamp)" />
                </svg>

                <div
                    class="absolute inset-x-0 bottom-0 h-[40%] opacity-(--g-firefly) transition-opacity duration-1000"
                    aria-hidden="true"
                >
                    <span
                        class="absolute bottom-[30%] left-[12%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)]"
                    ></span>
                    <span
                        class="absolute bottom-[55%] left-[31%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)]"
                        style="animation-delay: -2.3s"
                    ></span>
                    <span
                        class="absolute bottom-[40%] left-[52%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)]"
                        style="animation-delay: -4.1s"
                    ></span>
                    <span
                        class="absolute bottom-[25%] left-[86%] size-1 animate-firefly rounded-full bg-lime-200 shadow-[0_0_6px_2px_rgba(217,249,157,0.7)]"
                        style="animation-delay: -5.6s"
                    ></span>
                </div>

                {{-- 玻璃的反光 --}}
                <div
                    class="pointer-events-none absolute inset-0 bg-[linear-gradient(115deg,rgba(255,255,255,0.16)_0%,rgba(255,255,255,0.04)_38%,transparent_60%)]"
                    aria-hidden="true"
                ></div>
            </div>

            {{-- 窗櫺 --}}
            <div
                class="pointer-events-none absolute inset-y-0 left-1/2 w-2 -translate-x-1/2 transition-[background-color] duration-1000 sm:w-3"
                :style="frameStyle()"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute inset-x-0 top-1/2 h-2 -translate-y-1/2 transition-[background-color] duration-1000 sm:h-3"
                :style="frameStyle()"
                aria-hidden="true"
            ></div>
        </div>

        {{-- 窗台 --}}
        <div
            class="absolute top-[46%] left-1/2 h-2.5 w-[92%] max-w-[calc(64rem+2rem)] -translate-x-1/2 rounded-sm shadow-[0_4px_10px_rgba(0,0,0,0.25)] transition-[background-color] transition-[background] duration-1000 sm:h-3.5"
            :style="frameStyle()"
            aria-hidden="true"
        ></div>

        {{-- 牆上：正在做的事、倒數、進度、輪次、按鈕 --}}
        <div
            class="absolute inset-x-0 top-[50%] bottom-[18%] flex flex-col items-center justify-center gap-1.5 px-6 text-center sm:gap-2"
            :style="focusInkStyle()"
        >
            <p
                class="max-w-2xl truncate text-base font-semibold sm:text-xl"
                x-text="myActivityLabel()"
                data-testid="study-room-focus-activity"
            ></p>

            <p
                class="font-mono text-[clamp(3.25rem,min(11vw,16vh),7rem)] leading-none font-bold tracking-tight tabular-nums"
                x-text="myRemainingLabel()"
                data-testid="study-room-focus-countdown"
            ></p>

            <div
                class="h-1.5 w-56 overflow-hidden rounded-full sm:w-80"
                :class="focusProgressTrackClass()"
                role="progressbar"
                aria-label="計時進度"
                :aria-valuenow="progressPercent()"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div
                    class="h-full rounded-full bg-current transition-[width] duration-1000 ease-linear"
                    :style="progressStyle()"
                    data-testid="study-room-focus-progress-bar"
                ></div>
            </div>

            <div
                class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm sm:text-base"
            >
                <span
                    class="font-semibold"
                    x-text="timerPhaseLabel()"
                    data-testid="study-room-focus-phase"
                ></span>
                <span
                    x-show="isPomodoro()"
                    class="inline-flex items-center gap-1.5"
                    aria-hidden="true"
                >
                    <template x-for="dot in cycleDots()" :key="dot.id">
                        <span
                            class="size-2.5 rounded-full transition"
                            :class="focusDotClass(dot)"
                        ></span>
                    </template>
                </span>
                <span class="opacity-80" x-text="roundLabel()"></span>
                <span class="opacity-60" aria-hidden="true">·</span>
                <span class="opacity-80" x-text="timerEndsAtLabel()"></span>
            </div>

            <div
                class="mt-1 flex flex-wrap items-center justify-center gap-2 sm:mt-2"
            >
                <button
                    type="button"
                    x-show="canStartBreak()"
                    @click="startBreak()"
                    :disabled="panelBusy"
                    class="inline-flex items-center gap-1.5 rounded-full border px-5 py-2.5 text-sm font-semibold backdrop-blur transition disabled:opacity-50"
                    :class="focusChromeClass()"
                    data-testid="study-room-focus-start-break"
                >
                    <x-heroicon-o-sparkles class="size-4" />
                    <span
                        x-text="isLongBreakRound() ? '開始長休息' : '開始休息'"
                    ></span>
                </button>
                <button
                    type="button"
                    x-show="canStartNextRound()"
                    @click="startNextRound()"
                    :disabled="panelBusy"
                    class="inline-flex items-center gap-1.5 rounded-full border px-5 py-2.5 text-sm font-semibold backdrop-blur transition disabled:opacity-50"
                    :class="focusChromeClass()"
                    data-testid="study-room-focus-next-round"
                >
                    <x-heroicon-s-play class="size-4" />
                    <span x-text="nextRoundLabel()"></span>
                </button>
                <button
                    type="button"
                    @click="stopTimer()"
                    :disabled="panelBusy"
                    class="inline-flex items-center gap-1.5 rounded-full border px-5 py-2.5 text-sm font-medium backdrop-blur transition disabled:opacity-50"
                    :class="focusChromeClass()"
                    data-testid="study-room-focus-stop-timer"
                >
                    <x-heroicon-o-stop class="size-4" />
                    結束計時
                </button>
            </div>
        </div>

        {{-- 書桌：木頭桌面、闔上的書、馬克杯、檯燈 --}}
        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 h-[17%] bg-[linear-gradient(to_bottom,var(--c-desk-top),var(--c-desk-bottom))] shadow-[0_-8px_30px_rgba(0,0,0,0.3)] transition-[background] duration-1000"
            aria-hidden="true"
        >
            <div class="absolute inset-x-0 top-0 h-1 bg-white/15"></div>
            <div
                class="absolute inset-0 bg-[repeating-linear-gradient(90deg,transparent_0_9rem,rgba(0,0,0,0.06)_9rem_calc(9rem+2px))]"
            ></div>
            {{-- 書 --}}
            <div
                class="absolute top-[26%] left-[10%] h-[22%] w-28 rotate-[-4deg] rounded-sm bg-warm-800/85 shadow-md sm:w-40"
            >
                <div
                    class="absolute inset-y-0 left-0 w-2 rounded-l-sm bg-warm-900"
                ></div>
                <div
                    class="absolute top-1/2 right-4 left-6 h-px bg-white/20"
                ></div>
            </div>
            <div
                class="absolute top-[18%] left-[12%] h-[22%] w-24 rotate-[2deg] rounded-sm bg-sky-900/80 shadow-md sm:w-36"
            >
                <div
                    class="absolute inset-y-0 left-0 w-2 rounded-l-sm bg-sky-950"
                ></div>
            </div>
            {{-- 馬克杯 --}}
            <div
                class="absolute top-[20%] left-[34%] h-[38%] w-10 rounded-t-sm rounded-b-lg bg-warm-50/90 shadow-md sm:w-12"
            >
                <div
                    class="absolute top-[18%] -right-3 h-[45%] w-4 rounded-r-full border-4 border-l-0 border-warm-50/90"
                ></div>
                <div
                    class="absolute inset-x-1.5 top-1 h-1.5 rounded-full bg-warm-700/70"
                ></div>
            </div>
        </div>
        {{-- 檯燈：與操作面板上的同一盞燈，只是換上小房間的配色 --}}
        <svg
            class="pointer-events-none absolute right-[6%] bottom-[12%] h-[22%] w-auto overflow-visible"
            viewBox="0 0 48 48"
            aria-hidden="true"
        >
            <defs>
                <radialGradient id="study-room-focus-lamp-glow">
                    <stop offset="0%" stop-color="#ffe6b0" stop-opacity="0.85" />
                    <stop offset="40%" stop-color="#ffd682" stop-opacity="0.45" />
                    <stop offset="100%" stop-color="#ffd682" stop-opacity="0" />
                </radialGradient>
            </defs>

            {{-- 燈罩口的光暈 --}}
            <circle
                cx="12.13"
                cy="22.44"
                r="17"
                fill="url(#study-room-focus-lamp-glow)"
                class="transition-opacity duration-1000"
                style="opacity: var(--c-lamp)"
            />

            <g fill="var(--c-desk-bottom)">
                {{-- 底座：薄圓盤加上中央的小圓丘 --}}
                <path d="M29 41.8 Q35 35.5 41 41.8 Z" />
                <rect x="26" y="41.8" width="18" height="2.8" rx="1.4" />
                {{-- 燈桿與支臂 --}}
                <path
                    d="M35 39 L32.5 19 L19 12"
                    fill="none"
                    stroke="var(--c-desk-bottom)"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                {{-- 支臂關節 --}}
                <circle cx="32.5" cy="19" r="2.4" />
            </g>

            {{-- 燈罩 --}}
            <path
                d="M16.33 10.24
                   L21.67 13.76
                   Q21.83 21.35 19.31 27.17
                   Q8.83 27.45 4.95 17.71
                   Q9.3 13.1 16.33 10.24 Z"
                fill="var(--c-frame)"
            />

            {{-- 燈罩內側：熄著時是陰影，亮起時暖黃色疊上來 --}}
            <g transform="rotate(33.4 12.13 22.44)">
                <ellipse data-testid="study-room-lamp-shade-mouth" cx="12.13" cy="22.44" rx="8.6" ry="3" fill="var(--c-desk-bottom)" />
                <ellipse
                    cx="12.13"
                    cy="22.44"
                    rx="8.6"
                    ry="3"
                    fill="#ffd06a"
                    class="transition-opacity duration-1000"
                    style="opacity: var(--c-lamp)"
                />
            </g>

            {{-- 燈泡：藏在燈罩開口裡 --}}
            <circle data-testid="study-room-lamp-bulb" cx="12.13" cy="22.44" r="2.1" fill="#fff4d6" class="transition-opacity duration-1000" style="opacity: calc(0.35 + 0.65 * var(--c-lamp))" />

            {{-- 燈罩與支臂之間的轉軸 --}}
            <circle cx="19" cy="12" r="2.2" fill="var(--c-desk-bottom)" />
        </svg>
    </div>
</template>
