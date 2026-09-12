@push('head')
    @vite(['resources/js/echo.js'])
@endpush

<x-layout
    title="自習室 - NOU 小幫手"
    description="陪空大同學一起讀書的虛擬自習室：找個座位坐下、掛上暱稱與表情符號、跑一輪番茄鐘。"
>
    <div
        class="mx-auto max-w-6xl space-y-6"
        data-testid="study-room-page"
        x-data="nouStudyRoom({
                    roomState: {{ Js::encode($viewModel->roomState) }},
                    clientConfig: {{ Js::encode($viewModel->clientConfig) }},
                    subjects: {{ Js::encode($viewModel->subjects) }},
                    verbs: {{ Js::encode($viewModel->verbs) }},
                    hasSchedule: {{ Js::from($viewModel->hasSchedule) }},
                    needsProfile: {{ Js::from($viewModel->needsProfile) }},
                    profile: {{ Js::encode($viewModel->profile) }},
                    emojiChoices: {{ Js::encode($viewModel->emojiChoices) }},
                })"
    >
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="space-y-2">
                <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
                    自習室
                </h2>
                <p class="text-sm text-warm-600 dark:text-zinc-400">找個座位跟其他同學一起用功。自習室 24 小時開放。</p>
            </div>

            <div
                class="inline-flex items-center gap-2 self-start rounded-full bg-warm-100 px-4 py-2 text-sm font-medium text-warm-800 dark:bg-zinc-800 dark:text-zinc-200"
                x-cloak
                data-testid="study-room-site-total"
            >
                <x-heroicon-o-fire class="size-4 shrink-0" />
                今天大家一起專注了
                <span x-text="focusTotalLabel()"></span>
            </div>
        </div>

        @if (! $viewModel->hasSchedule)
            <x-card data-testid="study-room-needs-schedule">
                <div class="flex flex-col items-center gap-3 py-6 text-center">
                    <x-heroicon-o-table-cells
                        class="size-10 text-warm-400 dark:text-zinc-500"
                    />
                    <div class="space-y-1">
                        <h3
                            class="text-xl font-semibold text-warm-800 dark:text-zinc-200"
                        >
                            先建立課表才能進自習室
                        </h3>
                        <p class="text-sm text-warm-500 dark:text-zinc-400">自習室會用你的課表列出「你在讀什麼」的選項，所以需要先有一份儲存好的課表。</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-2">
                        <x-link-button
                            :href="route('schedules.create')"
                            variant="warm-dark"
                        >
                            <x-heroicon-o-plus class="size-4" />
                            建立我的課表
                        </x-link-button>
                        <x-link-button
                            :href="route('schedules.my')"
                            variant="secondary"
                        >
                            我已經有課表了
                        </x-link-button>
                    </div>
                </div>
            </x-card>
        @else
            <noscript>
                <x-card data-testid="study-room-noscript">
                    <p class="text-sm text-warm-600 dark:text-zinc-400">自習室的即時座位圖與計時器需要 JavaScript 才能運作，請啟用 JavaScript 後重新整理這個頁面。</p>
                </x-card>
            </noscript>

            <div
                x-cloak
                class="space-y-4"
                :class="heldSeatCode ? 'pb-28' : ''"
                data-testid="study-room-root"
            >
                {{-- 連線失敗提示 --}}
                <div
                    x-show="connectionFailed"
                    x-cloak
                    class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
                    data-testid="study-room-connection-error"
                >
                    <x-heroicon-o-exclamation-triangle
                        class="size-4 shrink-0"
                    />
                    <span
                        >目前無法連上自習室，自習室可能正在維護。如果問題持續，請聯絡站長。</span
                    >
                </div>

                {{-- 錯誤提示 --}}
                <div
                    x-show="errorMessage"
                    x-cloak
                    class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
                    data-testid="study-room-error-toast"
                >
                    <x-heroicon-o-exclamation-triangle
                        class="size-4 shrink-0"
                    />
                    <span x-text="errorMessage"></span>
                </div>

                {{-- 時鐘、個人資訊、公告板 --}}
                <div
                    class="flex flex-col gap-4 sm:flex-row"
                    data-testid="study-room-top-row"
                >
                    <div class="flex flex-col gap-4 sm:w-64 sm:shrink-0">
                        <x-card
                            class="text-center"
                            data-testid="study-room-clock"
                        >
                            <p
                                class="font-mono text-3xl font-bold text-warm-900 tabular-nums dark:text-zinc-100"
                                x-text="clockTimeLabel()"
                            ></p>
                            <p
                                class="mt-1 text-xs text-warm-500 dark:text-zinc-400"
                                x-text="clockDateLabel()"
                            ></p>
                        </x-card>

                        <x-card
                            class="cursor-pointer transition hover:border-warm-400 dark:hover:border-zinc-500"
                            data-testid="study-room-personal-info"
                            @click="openPersonalInfo()"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-2xl"
                                    x-text="profileEmoji"
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-semibold text-warm-900 dark:text-zinc-100"
                                        x-text="
                                            profileNickname || '尚未設定暱稱'
                                        "
                                    ></p>
                                    <p
                                        class="text-xs text-warm-500 dark:text-zinc-400"
                                        x-text="
                                            '今天專注了 ' +
                                            yourFocusTotalLabel()
                                        "
                                    ></p>
                                </div>
                                <button
                                    type="button"
                                    @click.stop="openPersonalInfo()"
                                    aria-label="編輯個人資料"
                                    data-testid="study-room-personal-info-edit"
                                    class="shrink-0 rounded-full p-1.5 text-warm-500 transition hover:bg-warm-100 hover:text-warm-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                                >
                                    <x-heroicon-o-pencil class="size-4" />
                                </button>
                            </div>
                        </x-card>
                    </div>

                    <x-card
                        title="公告板"
                        class="flex-1"
                        data-testid="study-room-announcement"
                    >
                        <div
                            class="prose prose-sm max-h-40 max-w-none overflow-y-auto prose-warm dark:prose-zinc dark:prose-invert"
                        >
                            {!! $viewModel->announcementHtml !!}
                        </div>
                    </x-card>
                </div>

                {{-- 個人資料：暱稱、表情符號、近期專注紀錄 --}}
                <x-modal
                    name="personalInfoOpen"
                    title="你的自習室資料"
                    maxWidth="max-w-lg"
                    data-testid="study-room-personal-info-modal"
                >
                    @include('study-room.partials._personal-info-form')

                    <div
                        class="mt-6 border-t border-warm-200 pt-4 dark:border-zinc-700"
                    >
                        <h4
                            class="mb-2 text-sm font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            最近的專注紀錄
                        </h4>

                        <p
                            x-show="sessionsLoading"
                            x-cloak
                            class="text-sm text-warm-500 dark:text-zinc-400"
                        >載入中…</p>

                        <p
                            x-show="
                                !sessionsLoading && recentSessions.length === 0
                            "
                            x-cloak
                            class="text-sm text-warm-500 dark:text-zinc-400"
                        >還沒有紀錄</p>

                        <ul
                            x-show="
                                !sessionsLoading && recentSessions.length > 0
                            "
                            x-cloak
                            class="space-y-2"
                            data-testid="study-room-session-log"
                        >
                            <template
                                x-for="session in recentSessions"
                                :key="session.startedAt"
                            >
                                <li
                                    class="flex items-center justify-between gap-2 text-sm"
                                >
                                    <span
                                        class="truncate text-warm-800 dark:text-zinc-200"
                                        x-text="session.activityLabel"
                                    ></span>
                                    <span
                                        class="shrink-0 text-warm-500 dark:text-zinc-400"
                                        x-text="sessionDurationLabel(session)"
                                    ></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </x-modal>

                {{-- 尚未設定暱稱：無法進入座位圖 --}}
                <x-card
                    x-show="needsProfile"
                    x-cloak
                    data-testid="study-room-needs-profile-placeholder"
                >
                    <p class="text-sm text-warm-600 dark:text-zinc-400">請先設定暱稱與表情符號，才能加入自習室。</p>
                </x-card>

                <div x-show="!needsProfile" class="space-y-6">
                    {{-- 樓層座位圖：由上往下看的閱覽室 --}}
                    <template x-for="floor in state.floors" :key="floor.floor">
                        <section
                            class="space-y-3"
                            :data-testid="'study-room-floor-' + floor.floor"
                        >
                            <div class="flex items-end justify-between px-1">
                                <h3
                                    class="flex items-center gap-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
                                >
                                    <span x-text="floor.label"></span>
                                    <span
                                        class="text-sm font-normal text-warm-500 dark:text-zinc-400"
                                        >閱覽室</span
                                    >
                                </h3>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-warm-100 px-2.5 py-1 text-xs text-warm-700 tabular-nums dark:bg-zinc-800 dark:text-zinc-300"
                                >
                                    <span
                                        class="size-1.5 rounded-full bg-emerald-500"
                                    ></span>
                                    <span
                                        x-text="
                                            floor.occupiedCount +
                                            ' / ' +
                                            floor.totalCount +
                                            ' 人在座'
                                        "
                                    ></span>
                                </span>
                            </div>

                            {{-- 房間：厚牆 + 木地板 --}}
                            <div
                                class="relative rounded-2xl border-[6px] border-warm-300 bg-warm-100/60 shadow-sm dark:border-zinc-600 dark:bg-zinc-900"
                            >
                                {{-- 上方牆面的窗戶：一排透光的窗格 --}}
                                <div
                                    class="pointer-events-none absolute inset-x-10 -top-[6px] z-10 flex h-[6px] gap-3 sm:inset-x-20"
                                    aria-hidden="true"
                                >
                                    <template x-for="pane in 4" :key="pane">
                                        <span
                                            class="flex-1 bg-sky-200 dark:bg-sky-900"
                                        ></span>
                                    </template>
                                </div>
                                <div
                                    class="pointer-events-none absolute inset-x-10 top-0 h-12 rounded-t-[10px] bg-gradient-to-b from-sky-100/60 to-transparent sm:inset-x-20 dark:from-sky-900/20"
                                    aria-hidden="true"
                                ></div>

                                {{-- 門：只有一樓有對外的門，樓上都是走樓梯 --}}
                                <template x-if="isGroundFloor(floor)">
                                    <div>
                                        <div
                                            class="pointer-events-none absolute right-8 -bottom-[6px] z-10 h-[6px] w-12 bg-warm-50 dark:bg-zinc-950"
                                            aria-hidden="true"
                                        ></div>
                                        <div
                                            class="pointer-events-none absolute right-8 bottom-0 z-10 size-12 rounded-tl-full border-t border-l border-dashed border-warm-400 dark:border-zinc-500"
                                            aria-hidden="true"
                                        >
                                            <span
                                                class="absolute right-0 bottom-0 h-full w-[3px] origin-bottom -rotate-[70deg] rounded-full bg-warm-500 dark:bg-zinc-400"
                                            ></span>
                                        </div>
                                    </div>
                                </template>
                                {{-- 木地板紋理 --}}
                                <div
                                    class="relative space-y-6 rounded-[10px] bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(0,0,0,0.04)_5.5rem_calc(5.5rem+1px))] px-4 pt-6 pb-16 sm:px-8 dark:bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(255,255,255,0.05)_5.5rem_calc(5.5rem+1px))]"
                                >
                                    {{-- 靠牆的單人閱覽桌 --}}
                                    <div
                                        class="grid grid-cols-3 justify-items-center gap-x-3 gap-y-7 sm:grid-cols-4 sm:gap-x-5 md:grid-cols-6"
                                        data-testid="study-room-solo-seats"
                                    >
                                        <template
                                            x-for="seat in floor.soloSeats"
                                            :key="seat.code"
                                        >
                                            <button
                                                type="button"
                                                @click="take(seat.code)"
                                                :disabled="seat.isOccupied ||
                                                busySeatCode !== null"
                                                :class="seatClasses(seat)"
                                                :data-testid="seatTestId(seat)"
                                                :aria-label="seatAriaLabel(
                                                    seat
                                                )"
                                            >
                                                {{-- 桌面與檯燈 --}}
                                                <span
                                                    class="pointer-events-none absolute inset-x-1.5 top-0 h-4 rounded-b-md bg-warm-200 shadow-[inset_0_-2px_0_var(--color-warm-300)] dark:bg-zinc-700 dark:shadow-[inset_0_-2px_0_var(--color-zinc-600)]"
                                                    aria-hidden="true"
                                                >
                                                    <span
                                                        class="absolute top-1 right-1.5 size-2 rounded-full transition"
                                                        :class="seat.isOccupied
                                                            ? 'bg-amber-400 shadow-[0_0_8px_3px_rgba(251,191,36,0.55)]'
                                                            : 'bg-warm-300 dark:bg-zinc-600'"
                                                    ></span>
                                                    <span
                                                        x-show="seat.isOccupied"
                                                        class="absolute top-1.5 left-2 h-1.5 w-4 rounded-[2px] bg-sky-400/80 dark:bg-sky-500/70"
                                                    ></span>
                                                </span>

                                                <template
                                                    x-if="!seat.isOccupied"
                                                >
                                                    <div
                                                        class="flex flex-col items-center gap-1"
                                                    >
                                                        {{-- 空椅子 --}}
                                                        <span
                                                            class="flex size-8 items-center justify-center rounded-lg border-2 border-b-4 border-warm-300 bg-white text-[10px] font-medium text-warm-400 transition group-hover:border-warm-400 group-hover:text-warm-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-500"
                                                            x-text="
                                                                seat.seatNumber
                                                            "
                                                        ></span>
                                                        <span
                                                            class="text-[10px] text-warm-400 opacity-0 transition group-hover:opacity-100 dark:text-zinc-500"
                                                            >點擊入座</span
                                                        >
                                                    </div>
                                                </template>
                                                <template
                                                    x-if="seat.isOccupied"
                                                >
                                                    <div
                                                        class="flex w-full flex-col items-center gap-0.5"
                                                    >
                                                        <template
                                                            x-if="
                                                                thoughtBubbleText(
                                                                    seat
                                                                )
                                                            "
                                                        >
                                                            <div
                                                                class="pointer-events-none absolute -top-6 left-1/2 z-10 flex w-24 -translate-x-1/2 overflow-hidden rounded-full border border-warm-200 bg-white px-2 py-0.5 shadow-sm dark:border-zinc-600 dark:bg-zinc-800"
                                                                data-testid="study-room-seat-bubble"
                                                            >
                                                                <span
                                                                    class="text-[9px] whitespace-nowrap text-warm-700 dark:text-zinc-200"
                                                                    :class="needsMarquee(
                                                                        seat
                                                                    )
                                                                        ? 'inline-block animate-marquee'
                                                                        : 'block truncate'"
                                                                    x-text="
                                                                        thoughtBubbleText(
                                                                            seat
                                                                        )
                                                                    "
                                                                ></span>
                                                            </div>
                                                        </template>

                                                        {{-- 椅子上的同學 --}}
                                                        <span class="relative">
                                                            <span
                                                                class="flex size-8 items-center justify-center rounded-lg border-2 border-b-4 border-warm-400 bg-white text-lg leading-none shadow-sm dark:border-zinc-500 dark:bg-zinc-800"
                                                                x-text="
                                                                    seat.emoji
                                                                "
                                                            ></span>
                                                            <span
                                                                x-show="
                                                                    isMine(seat)
                                                                "
                                                                class="absolute -top-1.5 -right-2 rounded-full bg-amber-500 px-1 text-[9px] leading-4 font-semibold text-white shadow-sm"
                                                                >你</span
                                                            >
                                                        </span>
                                                        <span
                                                            class="max-w-full truncate text-[10px] font-medium text-warm-800 dark:text-zinc-200"
                                                            x-text="
                                                                seat.nickname
                                                            "
                                                        ></span>
                                                        <span
                                                            class="font-mono text-[10px] text-warm-500 tabular-nums dark:text-zinc-400"
                                                            x-text="
                                                                timerLabel(seat)
                                                            "
                                                        ></span>
                                                    </div>
                                                </template>
                                            </button>
                                        </template>
                                    </div>

                                    {{-- 中間的共桌 --}}
                                    <div
                                        class="flex flex-wrap justify-center gap-x-8 gap-y-6 pt-2"
                                        data-testid="study-room-tables"
                                    >
                                        <template
                                            x-for="table in floor.tables"
                                            :key="table.groupCode"
                                        >
                                            <div
                                                class="flex flex-col items-center gap-1"
                                                :data-testid="'study-room-table-' +
                                                table.groupCode"
                                            >
                                                {{-- 上排椅子（椅背朝上） --}}
                                                <div class="flex gap-4">
                                                    <template
                                                        x-for="
                                                            seat in
                                                            tableSeatsRow(
                                                                table,
                                                                0
                                                            )
                                                        "
                                                        :key="seat.code"
                                                    >
                                                        @include('study-room.partials._table-chair', ['backrest' => 'border-t-4', 'timerSide' => 'top'])
                                                    </template>
                                                </div>

                                                {{-- 桌面 --}}
                                                <div
                                                    class="flex h-14 w-44 items-center justify-center gap-2 rounded-xl border-2 border-warm-300 bg-warm-200 shadow-[inset_0_2px_0_rgba(255,255,255,0.6),0_2px_4px_rgba(0,0,0,0.06)] dark:border-zinc-600 dark:bg-zinc-700 dark:shadow-none"
                                                >
                                                    <span
                                                        class="text-base leading-none"
                                                        aria-hidden="true"
                                                        >🪴</span
                                                    >
                                                    <span
                                                        class="text-xs font-medium text-warm-700 dark:text-zinc-300"
                                                        x-text="table.label"
                                                    ></span>
                                                </div>

                                                {{-- 下排椅子（椅背朝下） --}}
                                                <div class="flex gap-4">
                                                    <template
                                                        x-for="
                                                            seat in
                                                            tableSeatsRow(
                                                                table,
                                                                1
                                                            )
                                                        "
                                                        :key="seat.code"
                                                    >
                                                        @include('study-room.partials._table-chair', ['backrest' => 'border-b-4', 'timerSide' => 'bottom'])
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- 樓梯（左下角）：通往下一層 --}}
                                    <div
                                        class="pointer-events-none absolute bottom-0 left-3 flex flex-col items-start gap-1 sm:left-5"
                                        data-testid="study-room-stairs"
                                    >
                                        <span
                                            class="max-w-40 text-[10px] leading-tight text-warm-500 dark:text-zinc-400"
                                            x-text="stairHint(floor)"
                                        ></span>
                                        <span
                                            x-show="!isGroundFloor(floor)"
                                            class="text-[10px] leading-tight text-warm-500 dark:text-zinc-400"
                                            x-text="stairDownHint(floor)"
                                        ></span>
                                        <div
                                            class="relative h-9 w-16 rounded-t-sm border-x-2 border-t-2 border-warm-300 bg-[repeating-linear-gradient(180deg,var(--color-warm-100)_0_5px,var(--color-warm-300)_5px_6px)] dark:border-zinc-600 dark:bg-[repeating-linear-gradient(180deg,var(--color-zinc-800)_0_5px,var(--color-zinc-600)_5px_6px)]"
                                            aria-hidden="true"
                                        >
                                            <x-heroicon-o-arrow-up
                                                class="absolute top-1/2 left-1/2 size-4 -translate-x-1/2 -translate-y-1/2 text-warm-600 dark:text-zinc-300"
                                            />
                                        </div>
                                    </div>

                                    {{-- 靠牆的書櫃：留出左邊樓梯與右邊門口的空間 --}}
                                    <div
                                        class="pointer-events-none absolute right-24 bottom-0 left-24 flex h-5 items-end justify-center"
                                        aria-hidden="true"
                                    >
                                        <div
                                            class="h-4 w-full max-w-md rounded-t-sm border-x-2 border-t-2 border-warm-300 bg-[repeating-linear-gradient(90deg,var(--color-warm-500)_0_5px,var(--color-warm-50)_5px_6px,var(--color-warm-700)_6px_9px,var(--color-warm-50)_9px_10px,var(--color-sky-600)_10px_14px,var(--color-warm-50)_14px_15px,var(--color-emerald-600)_15px_21px,var(--color-warm-50)_21px_22px,var(--color-warm-400)_22px_25px,var(--color-warm-50)_25px_26px)] opacity-70 dark:border-zinc-600 dark:opacity-50"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </template>

                    <p class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-xs text-warm-500 dark:text-zinc-400">
                        <span class="inline-flex items-center gap-1.5">
                            <span
                                class="size-3 rounded-full border-2 border-b-[3px] border-warm-300 bg-white dark:border-zinc-600 dark:bg-zinc-800"
                            ></span>
                            空位
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span
                                class="size-3 rounded-full bg-amber-400 shadow-[0_0_6px_2px_rgba(251,191,36,0.5)]"
                            ></span>
                            有人（檯燈亮著）
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span
                                class="rounded-full bg-amber-500 px-1 text-[9px] leading-4 font-semibold text-white"
                                >你</span
                            >
                            你的座位
                        </span>
                    </p>
                </div>

                {{-- 操作面板：只有目前坐著的同學才會看到，固定在畫面底部 --}}
                <div
                    x-show="heldSeatCode"
                    x-cloak
                    class="fixed inset-x-0 bottom-0 z-40 border-t border-orange-300 bg-orange-50 px-4 py-3 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] shadow-[0_-4px_12px_rgba(0,0,0,0.06)] dark:border-orange-900 dark:bg-orange-950/20"
                    data-testid="study-room-control-panel"
                >
                    <div
                        class="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
                    >
                        <button
                            type="button"
                            @click="leave()"
                            :disabled="panelBusy"
                            data-testid="study-room-leave-seat"
                            class="inline-flex items-center justify-center gap-1 rounded-lg border border-warm-500 bg-white px-3 py-1.5 text-sm font-semibold text-warm-900 transition hover:bg-warm-50 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                        >
                            <x-heroicon-o-arrow-right-start-on-rectangle
                                class="size-4"
                            />
                            離開座位
                        </button>

                        {{-- 尚未開始計時：設定活動、科目與計時模式 --}}
                        <div
                            x-show="!hasRunningTimer()"
                            x-cloak
                            class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
                        >
                            <x-select
                                wrapperClass="w-full sm:w-40"
                                x-model="selectedVerb"
                                data-testid="study-room-verb-select"
                            >
                                @foreach ($viewModel->verbs as $verb)
                                    <option value="{{ $verb->value }}">
                                        {{ $verb->label }}
                                    </option>
                                @endforeach
                            </x-select>

                            <x-select
                                wrapperClass="w-full sm:w-48"
                                x-model="selectedSubjectCourseId"
                                data-testid="study-room-subject-select"
                            >
                                @foreach ($viewModel->subjects as $subject)
                                    <option value="{{ $subject->id ?? '' }}">
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </x-select>

                            <div class="flex flex-wrap items-center gap-3">
                                <label
                                    class="inline-flex items-center gap-1.5 text-sm"
                                >
                                    <input
                                        type="radio"
                                        value="pomodoro"
                                        x-model="timerMode"
                                        data-testid="study-room-mode-pomodoro"
                                    />
                                    番茄鐘（{{ config('study-room.timer.pomodoro.focus_minutes') }} 分鐘）
                                </label>
                                <label
                                    class="inline-flex items-center gap-1.5 text-sm"
                                >
                                    <input
                                        type="radio"
                                        value="custom"
                                        x-model="timerMode"
                                        data-testid="study-room-mode-custom"
                                    />
                                    自訂
                                </label>
                                <input
                                    type="number"
                                    x-show="timerMode === 'custom'"
                                    x-model.number="customMinutes"
                                    :min="config.timerCustomMinMinutes"
                                    :max="config.timerCustomMaxMinutes"
                                    data-testid="study-room-custom-minutes"
                                    class="w-24 rounded-lg border border-warm-200 px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <button
                                type="button"
                                @click="startTimer()"
                                :disabled="panelBusy"
                                data-testid="study-room-start-timer"
                                class="inline-flex items-center justify-center gap-1 rounded-lg border border-warm-700 bg-warm-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-warm-800 disabled:opacity-50 sm:ml-auto"
                            >
                                <x-heroicon-o-play class="size-4" />
                                開始專注
                            </button>
                        </div>

                        {{-- 計時中：顯示倒數、結束、開始休息 --}}
                        <div
                            x-show="hasRunningTimer() || canStartBreak()"
                            x-cloak
                            class="flex flex-1 items-center gap-3"
                        >
                            <p
                                class="font-mono text-2xl font-bold text-warm-900 tabular-nums dark:text-zinc-100"
                                data-testid="study-room-your-countdown"
                                x-text="
                                    mySeat() ? remainingLabel(mySeat()) : ''
                                "
                            ></p>

                            <div class="ml-auto flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="stopTimer()"
                                    :disabled="panelBusy"
                                    data-testid="study-room-stop-timer"
                                    class="inline-flex items-center gap-1 rounded-lg border border-warm-500 bg-white px-3 py-1.5 text-sm font-semibold text-warm-900 transition hover:bg-warm-50 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                                >
                                    結束
                                </button>

                                <button
                                    type="button"
                                    x-show="canStartBreak()"
                                    @click="startBreak()"
                                    :disabled="panelBusy"
                                    data-testid="study-room-start-break"
                                    class="inline-flex items-center gap-1 rounded-lg border border-emerald-600 bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                                >
                                    開始休息
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layout>
