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

                <div x-show="!needsProfile" class="space-y-4">
                    {{-- 樓層座位圖 --}}
                    <template x-for="floor in state.floors" :key="floor.floor">
                        <div
                            class="space-y-4 rounded-lg border border-warm-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                            :data-testid="'study-room-floor-' + floor.floor"
                        >
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-lg font-semibold text-warm-900 dark:text-zinc-100"
                                    x-text="floor.label"
                                ></h3>
                                <span
                                    class="text-xs text-warm-500 dark:text-zinc-400"
                                    x-text="
                                        floor.occupiedCount +
                                        ' / ' +
                                        floor.totalCount +
                                        ' 人'
                                    "
                                ></span>
                            </div>

                            {{-- 單人座 --}}
                            <div
                                class="grid grid-cols-4 gap-2 sm:grid-cols-6"
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
                                        :aria-label="seatAriaLabel(seat)"
                                    >
                                        {{-- 桌子（靠牆） --}}
                                        <span
                                            class="pointer-events-none absolute inset-x-2 top-0 h-3 rounded-b-sm border-x-2 border-b-2 border-warm-400 bg-warm-50 dark:border-zinc-500 dark:bg-zinc-800"
                                        ></span>

                                        <template x-if="!seat.isOccupied">
                                            <div
                                                class="flex flex-col items-center gap-0.5"
                                            >
                                                {{-- 椅子 --}}
                                                <span
                                                    class="flex size-5 items-center justify-center rounded-full border-2 border-warm-300 text-sm leading-none text-warm-300 dark:border-zinc-600 dark:text-zinc-600"
                                                    >+</span
                                                >
                                                <span
                                                    class="text-[10px] text-warm-400 dark:text-zinc-500"
                                                    x-text="seat.seatNumber"
                                                ></span>
                                            </div>
                                        </template>
                                        <template x-if="seat.isOccupied">
                                            <div
                                                class="flex flex-col items-center gap-0.5"
                                            >
                                                <template
                                                    x-if="
                                                        thoughtBubbleText(seat)
                                                    "
                                                >
                                                    <div
                                                        class="pointer-events-none absolute -top-7 left-1/2 z-10 w-24 -translate-x-1/2 overflow-hidden rounded-md border border-warm-200 bg-white px-1.5 py-0.5 shadow-sm dark:border-zinc-600 dark:bg-zinc-800"
                                                        data-testid="study-room-seat-bubble"
                                                    >
                                                        <span
                                                            class="block text-[9px] whitespace-nowrap text-warm-700 dark:text-zinc-200"
                                                            :class="needsMarquee(
                                                                seat
                                                            )
                                                                ? 'inline-block animate-marquee'
                                                                : 'truncate'"
                                                            x-text="
                                                                thoughtBubbleText(
                                                                    seat
                                                                )
                                                            "
                                                        ></span>
                                                    </div>
                                                </template>

                                                <span
                                                    class="font-mono text-[10px] text-warm-500 tabular-nums dark:text-zinc-400"
                                                    x-text="timerLabel(seat)"
                                                ></span>
                                                {{-- 椅子上的同學 --}}
                                                <span
                                                    class="flex size-6 items-center justify-center rounded-full border-2 border-warm-300 bg-white text-base leading-none dark:border-zinc-600 dark:bg-zinc-900"
                                                    x-text="seat.emoji"
                                                ></span>
                                                <span
                                                    class="max-w-full truncate text-[10px] font-medium text-warm-800 dark:text-zinc-200"
                                                    x-text="seat.nickname"
                                                ></span>
                                            </div>
                                        </template>
                                    </button>
                                </template>
                            </div>

                            {{-- 共桌 --}}
                            <div
                                class="flex flex-wrap justify-center gap-6 pt-2 sm:justify-start"
                                data-testid="study-room-tables"
                            >
                                <template
                                    x-for="table in floor.tables"
                                    :key="table.groupCode"
                                >
                                    <div
                                        class="flex flex-col items-center gap-1"
                                    >
                                        <div
                                            class="grid grid-cols-3 grid-rows-2 gap-1"
                                            :data-testid="'study-room-table-' +
                                            table.groupCode"
                                        >
                                            <div
                                                class="col-start-2 row-span-2 row-start-1 flex items-center justify-center rounded-sm border border-warm-300 bg-warm-100 text-[9px] font-medium whitespace-nowrap text-warm-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                                                style="
                                                    width: 2rem;
                                                    height: 5.5rem;
                                                    writing-mode: vertical-rl;
                                                "
                                                x-text="table.label"
                                            ></div>

                                            <template
                                                x-for="
                                                    (seat, index) in table.seats
                                                "
                                                :key="seat.code"
                                            >
                                                <button
                                                    type="button"
                                                    @click="take(seat.code)"
                                                    :disabled="seat.isOccupied ||
                                                    busySeatCode !== null"
                                                    :class="tableSeatPositionClass(
                                                        index
                                                    ) +
                                                    ' ' +
                                                    seatClasses(seat, 'table')"
                                                    style="
                                                        width: 2.5rem;
                                                        height: 2.5rem;
                                                        min-height: 0;
                                                        padding: 0;
                                                    "
                                                    :data-testid="seatTestId(
                                                        seat
                                                    )"
                                                    :aria-label="seatAriaLabel(
                                                        seat
                                                    )"
                                                >
                                                    <template
                                                        x-if="!seat.isOccupied"
                                                    >
                                                        <span
                                                            class="text-xs text-warm-300 dark:text-zinc-600"
                                                            >+</span
                                                        >
                                                    </template>
                                                    <template
                                                        x-if="seat.isOccupied"
                                                    >
                                                        <span
                                                            class="text-xs"
                                                            x-text="seat.emoji"
                                                        ></span>
                                                    </template>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
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
