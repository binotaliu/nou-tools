@push('head')
    @vite(['resources/js/echo.js'])
@endpush

<x-layout
    title="自習室 - NOU 小幫手"
    description="陪空大同學一起讀書的虛擬自習室：找個座位坐下、掛上暱稱與表情符號、跑一輪番茄鐘。"
>
    <div class="mx-auto max-w-6xl space-y-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="space-y-2">
                <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
                    自習室
                </h2>
                <p class="text-sm text-warm-600 dark:text-zinc-400">找個座位坐下，掛上暱稱與表情符號，讓遠距離讀書不再孤單。開放時間：{{ $viewModel->openHoursLabel }}。</p>
            </div>

            <div
                class="inline-flex items-center gap-2 self-start rounded-full bg-warm-100 px-4 py-2 text-sm font-medium text-warm-800 dark:bg-zinc-800 dark:text-zinc-200"
                data-testid="study-room-occupant-count"
            >
                <x-heroicon-o-user-group class="size-4 shrink-0" />
                目前在線 {{ $viewModel->roomState->totals->occupantCount }} 人
            </div>
        </div>

        {{-- 公告板 --}}
        <x-card title="公告板" data-testid="study-room-announcement">
            <div
                class="prose prose-sm max-w-none prose-warm dark:prose-zinc dark:prose-invert"
            >
                {!! $viewModel->announcementHtml !!}
            </div>
        </x-card>

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
            @if ($viewModel->needsProfile)
                <x-card
                    title="設定暱稱與表情符號"
                    subtitle="暱稱與表情符號會顯示給其他在自習室裡的同學看到。暱稱每 {{ config('study-room.nickname.cooldown_days') }} 天只能變更一次，表情符號則隨時可以換。"
                    data-testid="study-room-profile-form"
                >
                    <form
                        method="POST"
                        action="{{ route('study-room.profile.update') }}"
                        class="space-y-4"
                    >
                        @csrf

                        <div>
                            <label
                                for="nickname"
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                暱稱
                            </label>
                            <input
                                type="text"
                                id="nickname"
                                name="nickname"
                                value="{{ old('nickname', $viewModel->profile->nickname) }}"
                                minlength="{{ config('study-room.nickname.min_length') }}"
                                maxlength="{{ config('study-room.nickname.max_length') }}"
                                required
                                data-testid="study-room-nickname-input"
                                class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                            />
                        </div>

                        <div>
                            <span
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                選一個表情符號代表你
                            </span>
                            <div
                                class="flex flex-wrap gap-2"
                                data-testid="study-room-emoji-choices"
                            >
                                @foreach ($viewModel->emojiChoices as $emojiChoice)
                                    <label
                                        class="cursor-pointer rounded-lg border border-warm-200 px-3 py-2 text-xl has-[:checked]:border-warm-600 has-[:checked]:bg-warm-100 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-orange-300 dark:border-zinc-700 dark:has-[:checked]:border-warm-400 dark:has-[:checked]:bg-warm-900/40"
                                    >
                                        <input
                                            type="radio"
                                            name="emoji"
                                            value="{{ $emojiChoice }}"
                                            class="sr-only"
                                            data-testid="study-room-emoji-option"
                                            {{ old('emoji', $viewModel->profile->emoji) === $emojiChoice ? 'checked' : '' }}
                                            required
                                        />
                                        {{ $emojiChoice }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <x-button
                            type="submit"
                            variant="warm-dark"
                            data-testid="study-room-profile-submit"
                        >
                            <x-heroicon-o-check class="size-4" />
                            儲存
                        </x-button>
                    </form>
                </x-card>
            @endif

            <noscript>
                <x-card data-testid="study-room-noscript">
                    <p class="text-sm text-warm-600 dark:text-zinc-400">自習室的即時座位圖與計時器需要 JavaScript 才能運作，請啟用 JavaScript 後重新整理這個頁面。</p>
                </x-card>
            </noscript>

            <div
                x-data="nouStudyRoom({
                            roomState: {{ Js::encode($viewModel->roomState) }},
                            clientConfig: {{ Js::encode($viewModel->clientConfig) }},
                            subjects: {{ Js::encode($viewModel->subjects) }},
                            verbs: {{ Js::encode($viewModel->verbs) }},
                            hasSchedule: {{ Js::from($viewModel->hasSchedule) }},
                            needsProfile: {{ Js::from($viewModel->needsProfile) }},
                        })"
                x-cloak
                data-testid="study-room-root"
            >
                {{-- 狀態列：即時/輪詢指示、今日累積專注時數 --}}
                <div
                    class="flex flex-col gap-2 rounded-lg border border-warm-200 bg-white p-4 text-sm sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700 dark:bg-zinc-900"
                    data-testid="study-room-status-bar"
                >
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <p
                            class="text-warm-700 dark:text-zinc-300"
                            data-testid="study-room-site-total"
                        >
                            今天大家一起專注了
                            <span
                                class="font-semibold text-warm-900 dark:text-zinc-100"
                                x-text="focusTotalLabel()"
                            ></span>
                        </p>
                        <p
                            class="text-warm-700 dark:text-zinc-300"
                            data-testid="study-room-your-total"
                        >
                            你今天專注了
                            <span
                                class="font-semibold text-warm-900 dark:text-zinc-100"
                                x-text="yourFocusTotalLabel()"
                            ></span>
                        </p>
                    </div>

                    <div
                        class="inline-flex items-center gap-1.5 self-start rounded-full px-3 py-1 text-xs font-medium sm:self-auto"
                        :class="realtime
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                            : 'bg-warm-100 text-warm-700 dark:bg-zinc-800 dark:text-zinc-300'"
                        data-testid="study-room-connection-status"
                    >
                        <span
                            class="size-1.5 rounded-full"
                            :class="realtime
                                ? 'bg-emerald-500'
                                : 'bg-warm-400 dark:bg-zinc-500'"
                        ></span>
                        <span x-show="realtime">即時同步中</span>
                        <span x-show="!realtime">輪詢更新中</span>
                    </div>
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

                {{-- 操作面板：只有目前坐著的同學才會看到 --}}
                <div
                    x-show="heldSeatCode"
                    x-cloak
                    class="space-y-4 rounded-lg border border-orange-300 bg-orange-50 p-4 dark:border-orange-900 dark:bg-orange-950/20"
                    data-testid="study-room-control-panel"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <p class="text-sm font-semibold text-warm-900 dark:text-zinc-100">
                            你的座位：
                            <span x-text="heldSeatCode"></span>
                        </p>
                        <button
                            type="button"
                            @click="leave()"
                            :disabled="panelBusy"
                            data-testid="study-room-leave-seat"
                            class="inline-flex items-center gap-1 rounded-lg border border-warm-500 bg-white px-3 py-1.5 text-sm font-semibold text-warm-900 transition hover:bg-warm-50 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                        >
                            <x-heroicon-o-arrow-right-start-on-rectangle
                                class="size-4"
                            />
                            離開座位
                        </button>
                    </div>

                    {{-- 尚未開始計時：設定活動、科目與計時模式 --}}
                    <div x-show="!hasRunningTimer()" x-cloak class="space-y-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-warm-700 dark:text-zinc-300"
                                >
                                    你在做什麼
                                </label>
                                <select
                                    x-model="selectedVerb"
                                    data-testid="study-room-verb-select"
                                    class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                >
                                    @foreach ($viewModel->verbs as $verb)
                                        <option value="{{ $verb->value }}">
                                            {{ $verb->label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-warm-700 dark:text-zinc-300"
                                >
                                    科目
                                </label>
                                <select
                                    x-model="selectedSubjectCourseId"
                                    data-testid="study-room-subject-select"
                                    class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                >
                                    @foreach ($viewModel->subjects as $subject)
                                        <option
                                            value="{{ $subject->id ?? '' }}"
                                        >
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

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
                            class="inline-flex items-center gap-1 rounded-lg border border-warm-700 bg-warm-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-warm-800 disabled:opacity-50"
                        >
                            <x-heroicon-o-play class="size-4" />
                            開始專注
                        </button>
                    </div>

                    {{-- 計時中：顯示倒數、結束、開始休息 --}}
                    <div
                        x-show="hasRunningTimer() || canStartBreak()"
                        x-cloak
                        class="flex flex-wrap items-center gap-3"
                    >
                        <p
                            class="font-mono text-2xl font-bold text-warm-900 tabular-nums dark:text-zinc-100"
                            data-testid="study-room-your-countdown"
                            x-text="mySeat() ? remainingLabel(mySeat()) : ''"
                        ></p>

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
                                    <template x-if="!seat.isOccupied">
                                        <div
                                            class="flex flex-col items-center gap-0.5"
                                        >
                                            <span
                                                class="text-lg text-warm-300 dark:text-zinc-600"
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
                                            <span
                                                class="text-lg"
                                                x-text="seat.emoji"
                                            ></span>
                                            <span
                                                class="max-w-full truncate text-[10px] font-medium text-warm-800 dark:text-zinc-200"
                                                x-text="seat.nickname"
                                            ></span>
                                            <span
                                                class="max-w-full truncate text-[9px] text-warm-500 dark:text-zinc-400"
                                                x-text="
                                                    occupantStatusLabel(seat)
                                                "
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
                                <div class="flex flex-col items-center gap-1">
                                    <span
                                        class="text-xs font-medium text-warm-600 dark:text-zinc-400"
                                        x-text="table.label"
                                    ></span>
                                    <div
                                        class="grid grid-cols-3 grid-rows-3 gap-1"
                                        :data-testid="'study-room-table-' +
                                        table.groupCode"
                                    >
                                        <div
                                            class="col-start-2 row-start-2 flex items-center justify-center rounded-full border border-warm-300 bg-warm-100 text-[9px] text-warm-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                                            style="
                                                width: 2.25rem;
                                                height: 2.25rem;
                                            "
                                        >
                                            桌
                                        </div>

                                        <template
                                            x-for="(seat, index) in table.seats"
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
                                                seatClasses(seat)"
                                                style="
                                                    width: 2.5rem;
                                                    height: 2.5rem;
                                                    min-height: 0;
                                                    padding: 0;
                                                "
                                                :data-testid="seatTestId(seat)"
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
        @endif
    </div>
</x-layout>
