{{--
    自習室入口那面牆，畫法跟樓下的閱覽室一樣是平面的：厚厚的一圈牆、圓角、平塗的顏色。
    牆上開了一扇窗，窗外是跟專注模式同一片天與同一座花園；旁邊掛著鐘，
    再過去是釘著公告的佈告欄，底下釘著你自己的名牌。
    牆面本身不跟著天色走（那是專注模式的事），只有窗裡的景色與窗下的光會變。
--}}
<div
    class="relative overflow-hidden rounded-2xl border-[6px] border-warm-300 bg-warm-100/60 shadow-sm dark:border-zinc-600 dark:bg-zinc-900"
    data-testid="study-room-wall"
>
    {{-- 踢腳板 --}}
    <div
        class="pointer-events-none absolute inset-x-0 bottom-0 h-3 border-t-2 border-warm-300 bg-warm-200 dark:border-zinc-600 dark:bg-zinc-800"
        aria-hidden="true"
    ></div>

    <div
        class="relative flex flex-col gap-5 p-4 pb-7 sm:flex-row sm:items-start sm:gap-6 sm:p-6 sm:pb-9"
    >
        {{-- 窗：木框加十字窗櫺，窗外是此刻的校園 --}}
        <div class="relative shrink-0 sm:w-56 md:w-64">
            {{-- 窗光灑進來：白天是天色，夜裡只剩淡淡月光 --}}
            <div
                class="pointer-events-none absolute inset-x-0 top-6 h-32 transition-[background] duration-1000"
                :style="windowLightStyle()"
                aria-hidden="true"
            ></div>

            <div
                class="relative h-40 overflow-hidden rounded-lg border-4 border-warm-300 shadow-sm sm:h-44 dark:border-zinc-600"
            >
                @include('study-room.partials._garden-scene', ['class' => 'absolute inset-0'])

                {{-- 窗櫺 --}}
                <div
                    class="pointer-events-none absolute inset-y-0 left-1/2 w-1.5 -translate-x-1/2 bg-warm-300 dark:bg-zinc-600"
                    aria-hidden="true"
                ></div>
                <div
                    class="pointer-events-none absolute inset-x-0 top-[46%] h-1.5 bg-warm-300 dark:bg-zinc-600"
                    aria-hidden="true"
                ></div>
            </div>

            {{-- 窗台，上面擺了一盆跟共桌同款的小盆栽 --}}
            <div class="relative">
                <div
                    class="-mx-1.5 h-2 rounded-full bg-warm-300 dark:bg-zinc-600"
                    aria-hidden="true"
                ></div>
                <span
                    class="absolute -top-3.5 right-4 text-base leading-none"
                    aria-hidden="true"
                    >🪴</span
                >
            </div>
        </div>

        {{-- 掛鐘：指針走的是台灣時間，跟底下的數字同一個 --}}
        <div
            class="flex shrink-0 flex-col items-center gap-1.5 self-center sm:mt-2 sm:self-start"
            data-testid="study-room-clock"
        >
            <div
                class="relative size-20 rounded-full border-2 border-b-4 border-warm-300 bg-white shadow-sm sm:size-24 dark:border-zinc-600 dark:bg-zinc-800"
                aria-hidden="true"
            >
                {{-- 鐘面刻度：整點一格，三小時一格長的 --}}
                <template x-for="tick in 12" :key="tick">
                    <span
                        class="absolute top-0 left-1/2 h-1/2 w-0.5 origin-bottom"
                        :style="clockTickStyle(tick)"
                    >
                        <span
                            class="block w-full rounded-full bg-warm-300 dark:bg-zinc-600"
                            :class="clockTickClass(tick)"
                        ></span>
                    </span>
                </template>

                {{-- 時針、分針、秒針 --}}
                <span
                    class="absolute bottom-1/2 left-1/2 h-[26%] w-1 origin-bottom rounded-full bg-warm-700 dark:bg-zinc-300"
                    :style="clockHandStyle('hour')"
                    data-testid="study-room-clock-hour-hand"
                ></span>
                <span
                    class="absolute bottom-1/2 left-1/2 h-[36%] w-0.5 origin-bottom rounded-full bg-warm-700 dark:bg-zinc-300"
                    :style="clockHandStyle('minute')"
                    data-testid="study-room-clock-minute-hand"
                ></span>
                <span
                    class="absolute bottom-1/2 left-1/2 h-[40%] w-px origin-bottom rounded-full bg-amber-500"
                    :style="clockHandStyle('second')"
                ></span>
                <span
                    class="absolute top-1/2 left-1/2 size-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-warm-700 dark:bg-zinc-300"
                ></span>
            </div>

            <p class="text-center">
                <span
                    class="block font-mono text-sm leading-tight font-semibold text-warm-800 tabular-nums dark:text-zinc-200"
                    x-text="clockTimeLabel()"
                ></span>
                <span
                    class="block text-[10px] text-warm-500 dark:text-zinc-400"
                    x-text="clockDateLabel()"
                ></span>
            </p>
        </div>

        {{-- 佈告欄與名牌 --}}
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div
                class="rounded-xl border-2 border-b-4 border-warm-300 bg-warm-200 p-3 shadow-sm dark:border-zinc-600 dark:bg-zinc-800"
                data-testid="study-room-announcement"
            >
                <div
                    class="relative rounded-lg border border-warm-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    {{-- 圖釘 --}}
                    <span
                        class="absolute -top-1 left-5 size-2 rounded-full bg-rose-400 shadow-sm"
                        aria-hidden="true"
                    ></span>
                    <span
                        class="absolute -top-1 right-5 size-2 rounded-full bg-sky-400 shadow-sm"
                        aria-hidden="true"
                    ></span>

                    <h2
                        class="mb-1.5 text-sm font-semibold text-warm-900 dark:text-zinc-100"
                    >
                        公告板
                    </h2>
                    <div
                        class="prose prose-sm max-h-36 max-w-none overflow-y-auto prose-warm dark:prose-zinc dark:prose-invert"
                    >
                        {!! $viewModel->announcementHtml !!}
                    </div>
                </div>
            </div>

            {{-- 釘在牆上的名牌：點一下改暱稱與表情符號 --}}
            <div
                class="relative -rotate-1 cursor-pointer rounded-lg border-2 border-b-4 border-warm-300 bg-white px-4 py-2.5 shadow-sm transition hover:rotate-0 hover:border-warm-400 dark:border-zinc-600 dark:bg-zinc-800 dark:hover:border-zinc-500"
                data-testid="study-room-personal-info"
                @click="openPersonalInfo()"
            >
                <span
                    class="absolute -top-1 left-1/2 size-2 -translate-x-1/2 rounded-full bg-amber-400 shadow-sm"
                    aria-hidden="true"
                ></span>

                <div class="flex items-center gap-3">
                    <span class="text-2xl" x-text="profileEmoji"></span>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-semibold text-warm-900 dark:text-zinc-100"
                            x-text="profileNickname || '尚未設定暱稱'"
                        ></p>
                        <p
                            class="text-xs text-warm-500 dark:text-zinc-400"
                            x-text="'今天專注了 ' + yourFocusTotalLabel()"
                        ></p>
                    </div>
                    <button
                        type="button"
                        @click.stop="openStats()"
                        aria-label="檢視專注紀錄與統計"
                        data-testid="study-room-personal-info-stats"
                        class="shrink-0 rounded-full p-1.5 text-warm-500 transition hover:bg-warm-100 hover:text-warm-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
                    >
                        <x-heroicon-o-chart-bar class="size-4" />
                    </button>
                    <button
                        type="button"
                        @click.stop="openPersonalInfo()"
                        aria-label="編輯個人資料"
                        data-testid="study-room-personal-info-edit"
                        class="shrink-0 rounded-full p-1.5 text-warm-500 transition hover:bg-warm-100 hover:text-warm-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
                    >
                        <x-heroicon-o-pencil class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
