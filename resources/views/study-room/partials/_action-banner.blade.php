{{--
    操作面板：你的書桌。只有目前坐著的同學才會看到，固定在畫面底部。
    還沒計時：選活動、科目、計時方式後開始；計時中：倒數、進度、番茄鐘的輪次，
    以及開始休息 / 下一輪 / 結束 / 離開座位。桌面上緣那條細線就是進度條，
    左上角的檯燈在計時中會亮起來。
--}}
<div
    x-show="heldSeatCode"
    x-cloak
    x-transition:enter="transition duration-300 ease-out"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    class="fixed inset-x-0 bottom-0 z-40"
    data-testid="study-room-control-panel"
>
    <div class="mx-auto max-w-6xl sm:px-4">
        <div
            class="relative overflow-hidden border-t-[6px] border-warm-300 bg-warm-50 bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(0,0,0,0.035)_5.5rem_calc(5.5rem+1px))] shadow-[0_-10px_40px_rgba(0,0,0,0.14)] sm:rounded-t-2xl sm:border-x-[6px] dark:border-zinc-600 dark:bg-zinc-900 dark:bg-[repeating-linear-gradient(90deg,transparent_0_5.5rem,rgba(255,255,255,0.05)_5.5rem_calc(5.5rem+1px))]"
        >
            {{-- 檯燈的光暈：計時中亮起 --}}
            <div
                class="pointer-events-none absolute -top-16 -left-12 size-64 rounded-full bg-amber-300/50 blur-3xl transition-opacity duration-1000 dark:bg-amber-400/25"
                :class="hasTimer() ? 'opacity-100' : 'opacity-0'"
                aria-hidden="true"
            ></div>

            {{-- 進度條：沿著桌面的上緣 --}}
            <div
                x-show="hasTimer()"
                x-cloak
                class="absolute inset-x-0 top-0 h-1.5 bg-warm-200/80 dark:bg-zinc-800"
                role="progressbar"
                aria-label="計時進度"
                :aria-valuenow="progressPercent()"
                aria-valuemin="0"
                aria-valuemax="100"
                data-testid="study-room-progress"
            >
                <div
                    class="h-full rounded-r-full transition-[width] duration-1000 ease-linear"
                    :class="progressBarClass()"
                    :style="progressStyle()"
                    data-testid="study-room-progress-bar"
                ></div>
            </div>

            <div
                class="relative px-4 pt-5 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:px-6 sm:pt-6 sm:pb-[calc(env(safe-area-inset-bottom)+1.25rem)]"
            >
                {{-- 尚未開始計時：設定活動、科目與計時模式 --}}
                <div
                    x-show="!hasTimer()"
                    x-cloak
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:gap-6"
                    data-testid="study-room-timer-form"
                >
                    <div
                        class="flex items-center gap-3 lg:w-48 lg:shrink-0 lg:self-center"
                    >
                        @include('study-room.partials._desk-lamp', ['class' => 'size-12 shrink-0'])
                        <div class="min-w-0">
                            <p class="truncate text-xs text-warm-500 dark:text-zinc-400">
                                你的座位 · <span x-text="mySeatLabel()"></span>
                            </p>
                            <p class="text-lg font-semibold text-warm-900 dark:text-zinc-100">準備開始專注</p>
                        </div>
                    </div>

                    <div
                        class="grid flex-1 gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(8rem,1fr)_minmax(10rem,1.3fr)_auto]"
                    >
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
                                >活動</span
                            >
                            <x-select
                                x-model="selectedVerb"
                                data-testid="study-room-verb-select"
                            >
                                @foreach ($viewModel->verbs as $verb)
                                    <option value="{{ $verb->value }}">
                                        {{ $verb->label }}
                                    </option>
                                @endforeach
                            </x-select>
                        </label>

                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
                                >科目</span
                            >
                            <x-select
                                x-model="selectedSubjectCourseId"
                                data-testid="study-room-subject-select"
                            >
                                @foreach ($viewModel->subjects as $subject)
                                    <option value="{{ $subject->id ?? '' }}">
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </x-select>
                        </label>

                        <div class="sm:col-span-2 xl:col-span-1">
                            <span
                                class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
                                >計時方式</span
                            >
                            <div class="flex flex-wrap items-center gap-2">
                                {{-- 番茄鐘 / 自訂 --}}
                                <div
                                    class="inline-flex rounded-lg border border-warm-200 bg-white p-0.5 dark:border-zinc-700 dark:bg-zinc-900"
                                    role="radiogroup"
                                    aria-label="計時方式"
                                >
                                    <label
                                        class="cursor-pointer rounded-md px-3 py-1.5 text-sm font-medium transition has-checked:bg-warm-700 has-checked:text-white dark:has-checked:bg-warm-500 dark:has-checked:text-zinc-950"
                                    >
                                        <input
                                            type="radio"
                                            value="pomodoro"
                                            x-model="timerMode"
                                            class="sr-only"
                                            data-testid="study-room-mode-pomodoro"
                                        />
                                        番茄鐘
                                    </label>
                                    <label
                                        class="cursor-pointer rounded-md px-3 py-1.5 text-sm font-medium transition has-checked:bg-warm-700 has-checked:text-white dark:has-checked:bg-warm-500 dark:has-checked:text-zinc-950"
                                    >
                                        <input
                                            type="radio"
                                            value="custom"
                                            x-model="timerMode"
                                            class="sr-only"
                                            data-testid="study-room-mode-custom"
                                        />
                                        自訂
                                    </label>
                                </div>

                                {{-- 番茄鐘：目前的循環設定 --}}
                                <button
                                    type="button"
                                    x-show="timerMode === 'pomodoro'"
                                    @click="openCycleSettings()"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-warm-300 px-2.5 py-1.5 text-xs text-warm-700 transition hover:border-warm-400 hover:bg-white dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                    data-testid="study-room-cycle-settings"
                                >
                                    <span x-text="cycleChipLabel()"></span>
                                    <x-heroicon-o-adjustments-horizontal
                                        class="size-3.5 shrink-0"
                                    />
                                </button>

                                {{-- 自訂：分鐘數 --}}
                                <label
                                    x-show="timerMode === 'custom'"
                                    x-cloak
                                    class="inline-flex items-center gap-1.5 text-sm text-warm-700 dark:text-zinc-300"
                                >
                                    <input
                                        type="number"
                                        x-model.number="customMinutes"
                                        :min="config.timerCustomMinMinutes"
                                        :max="config.timerCustomMaxMinutes"
                                        data-testid="study-room-custom-minutes"
                                        class="w-20 rounded-lg border border-warm-200 bg-white px-2 py-1.5 text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                    />
                                    分鐘
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 lg:shrink-0">
                        <button
                            type="button"
                            @click="startTimer()"
                            :disabled="panelBusy"
                            data-testid="study-room-start-timer"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-warm-700 px-6 py-3 text-base font-semibold text-white shadow-md shadow-warm-700/20 transition hover:bg-warm-800 disabled:opacity-50 lg:flex-none dark:bg-warm-500 dark:text-zinc-950 dark:hover:bg-warm-400"
                        >
                            <x-heroicon-s-play class="size-5" />
                            開始專注
                        </button>

                        <button
                            type="button"
                            @click="leave()"
                            :disabled="panelBusy"
                            data-testid="study-room-leave-seat"
                            class="inline-flex items-center justify-center gap-1 rounded-xl border border-warm-300 bg-white/70 px-4 py-3 text-sm font-medium text-warm-800 transition hover:bg-white disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            <x-heroicon-o-arrow-right-start-on-rectangle
                                class="size-4"
                            />
                            離開座位
                        </button>
                    </div>
                </div>

                {{-- 計時中：倒數、輪次、開始休息 / 下一輪 / 結束 --}}
                <div
                    x-show="hasTimer()"
                    x-cloak
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6"
                    data-testid="study-room-timer-panel"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-4">
                        @include('study-room.partials._desk-lamp', ['class' => 'size-14 shrink-0'])
                        <div class="min-w-0">
                            <p
                                class="text-xs font-semibold tracking-wide"
                                :class="timerPhaseClass()"
                                x-text="timerPhaseLabel()"
                                data-testid="study-room-timer-phase"
                            ></p>
                            <p
                                class="truncate text-xl font-semibold text-warm-900 dark:text-zinc-100"
                                x-text="myActivityLabel()"
                                data-testid="study-room-timer-activity"
                            ></p>
                            <div
                                class="mt-1.5 flex items-center gap-2 text-xs text-warm-500 dark:text-zinc-400"
                            >
                                <span
                                    x-show="isPomodoro()"
                                    class="inline-flex items-center gap-1"
                                    data-testid="study-room-cycle-dots"
                                    aria-hidden="true"
                                >
                                    <template
                                        x-for="dot in cycleDots()"
                                        :key="dot.id"
                                    >
                                        <span
                                            class="size-2 rounded-full transition"
                                            :class="cycleDotClass(dot)"
                                        ></span>
                                    </template>
                                </span>
                                <span
                                    x-text="roundLabel()"
                                    data-testid="study-room-round-label"
                                ></span>
                                <span aria-hidden="true">·</span>
                                <span x-text="timerEndsAtLabel()"></span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center lg:px-4">
                        <p
                            class="font-mono text-5xl leading-none font-bold text-warm-900 tabular-nums sm:text-6xl dark:text-zinc-100"
                            data-testid="study-room-your-countdown"
                            x-text="myRemainingLabel()"
                        ></p>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-2 lg:justify-end"
                    >
                        <button
                            type="button"
                            @click="openFocusMode()"
                            data-testid="study-room-focus-mode-open"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-warm-300 bg-white/70 px-3 py-2.5 text-sm font-medium text-warm-800 transition hover:bg-white dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            title="全螢幕專注"
                        >
                            <x-heroicon-o-arrows-pointing-out class="size-4" />
                            全螢幕
                        </button>

                        <button
                            type="button"
                            x-show="canStartBreak()"
                            x-cloak
                            @click="startBreak()"
                            :disabled="panelBusy"
                            data-testid="study-room-start-break"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <x-heroicon-o-sparkles class="size-4" />
                            <span
                                x-text="
                                    isLongBreakRound()
                                        ? '開始長休息'
                                        : '開始休息'
                                "
                            ></span>
                        </button>

                        <button
                            type="button"
                            x-show="canStartNextRound()"
                            x-cloak
                            @click="startNextRound()"
                            :disabled="panelBusy"
                            data-testid="study-room-next-round"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-warm-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-warm-700/20 transition hover:bg-warm-800 disabled:opacity-50 dark:bg-warm-500 dark:text-zinc-950 dark:hover:bg-warm-400"
                        >
                            <x-heroicon-s-play class="size-4" />
                            <span x-text="nextRoundLabel()"></span>
                        </button>

                        <button
                            type="button"
                            @click="stopTimer()"
                            :disabled="panelBusy"
                            data-testid="study-room-stop-timer"
                            class="inline-flex items-center gap-1 rounded-xl border border-warm-300 bg-white/70 px-3 py-2.5 text-sm font-medium text-warm-800 transition hover:bg-white disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            <x-heroicon-o-stop class="size-4" />
                            結束
                        </button>

                        <button
                            type="button"
                            @click="leave()"
                            :disabled="panelBusy"
                            data-testid="study-room-leave-seat-running"
                            class="inline-flex items-center gap-1 rounded-xl px-3 py-2.5 text-sm font-medium text-warm-600 transition hover:bg-white/70 hover:text-warm-900 disabled:opacity-50 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                        >
                            <x-heroicon-o-arrow-right-start-on-rectangle
                                class="size-4"
                            />
                            離開座位
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 番茄鐘循環設定 --}}
<x-modal
    name="cycleSettingsOpen"
    title="設定你的番茄鐘"
    description="幾分鐘專注、休息多久、幾輪之後長休息一次。設定會記住，下次坐下時沿用。"
    data-testid="study-room-cycle-modal"
>
    <div class="grid grid-cols-2 gap-3">
        <label class="block">
            <span
                class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
                >專注（分鐘）</span
            >
            <input
                type="number"
                x-model.number="cycle.focusMinutes"
                :min="cycleBound('focus', 0)"
                :max="cycleBound('focus', 1)"
                data-testid="study-room-cycle-focus"
                class="w-full rounded-lg border border-warm-200 bg-white px-3 py-2 text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            />
        </label>
        <label class="block">
            <span
                class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
                >短休息（分鐘）</span
            >
            <input
                type="number"
                x-model.number="cycle.shortBreakMinutes"
                :min="cycleBound('break', 0)"
                :max="cycleBound('break', 1)"
                data-testid="study-room-cycle-short-break"
                class="w-full rounded-lg border border-warm-200 bg-white px-3 py-2 text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            />
        </label>
        <label class="block">
            <span
                class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
                >長休息（分鐘）</span
            >
            <input
                type="number"
                x-model.number="cycle.longBreakMinutes"
                :min="cycleBound('break', 0)"
                :max="cycleBound('break', 1)"
                data-testid="study-room-cycle-long-break"
                class="w-full rounded-lg border border-warm-200 bg-white px-3 py-2 text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            />
        </label>
        <label class="block">
            <span
                class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
                >每幾輪長休息一次</span
            >
            <input
                type="number"
                x-model.number="cycle.roundsPerCycle"
                :min="cycleBound('rounds', 0)"
                :max="cycleBound('rounds', 1)"
                data-testid="study-room-cycle-rounds"
                class="w-full rounded-lg border border-warm-200 bg-white px-3 py-2 text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            />
        </label>
    </div>

    <p class="mt-4 rounded-lg bg-warm-100 px-3 py-2 text-sm text-warm-700 dark:bg-zinc-800 dark:text-zinc-300">
        <span
            x-text="cycleSummaryLabel()"
            data-testid="study-room-cycle-summary"
        ></span>
    </p>

    <x-slot:footer>
        <div class="flex w-full items-center justify-between gap-2">
            <button
                type="button"
                @click="resetCycle()"
                class="text-sm text-warm-600 underline-offset-2 hover:underline dark:text-zinc-400"
            >
                恢復預設
            </button>
            <button
                type="button"
                @click="closeCycleSettings()"
                data-testid="study-room-cycle-done"
                class="inline-flex items-center rounded-lg bg-warm-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-warm-800 dark:bg-warm-500 dark:text-zinc-950 dark:hover:bg-warm-400"
            >
                完成
            </button>
        </div>
    </x-slot:footer>
</x-modal>
