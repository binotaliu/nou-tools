{{--
    School Schedule Calendar Component. Rendered on the client because the
    countdown and "still active" filtering must track the viewer's own
    clock (offline caching, long-lived tabs) — but the calendar itself is
    Taipei's academic calendar, so status/days-until stay anchored to
    Asia/Taipei rather than the viewer's local date (see nouSchoolCalendar
    in resources/js/app.js).
--}}
@if (! empty($events))
    <x-card
        {{ $attributes->merge(['title' => '學校行事曆']) }}
        x-data="nouSchoolCalendar({{ Js::from($events) }}, {{ Js::from($showPastEvents) }})"
    >
        <div class="flex flex-col md:flex-row md:items-start md:gap-6">
            {{-- Countdown (mobile 上方，桌面右側 1/3) --}}
            <template x-if="countdownEvent">
                <div
                    :class="activeEvents.length
                        ? 'order-first md:order-last md:w-1/3'
                        : 'order-first md:w-full'"
                    class="w-full print:hidden"
                >
                    <div
                        class="mb-4 rounded-lg border border-warm-200 bg-warm-50 p-4 dark:border-zinc-700 dark:bg-zinc-950"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <div
                                    class="font-semibold text-warm-800 dark:text-zinc-200"
                                    x-text="countdownEvent.name"
                                ></div>
                                <p
                                    class="mt-1 text-sm text-warm-600 tabular-nums dark:text-zinc-400"
                                    x-text="dateRange(countdownEvent)"
                                ></p>
                            </div>
                            <div class="text-right">
                                <template
                                    x-if="countdownEvent.status === 'ongoing'"
                                >
                                    <div
                                        class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800"
                                    >
                                        進行中
                                    </div>
                                </template>
                                <template
                                    x-if="countdownEvent.status !== 'ongoing'"
                                >
                                    <div>
                                        <div
                                            class="text-3xl font-bold text-warm-700 dark:text-zinc-300"
                                            x-text="countdownEvent.daysUntil"
                                        ></div>
                                        <div
                                            class="text-sm text-warm-500 dark:text-zinc-400"
                                        >
                                            天後
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{--
                Schedule Events (手機在 countdown 之下，桌面佔 2/3)
                當有 countdownEvent 時：不從列表移除該項目；在渲染時把該行於畫面上隱藏、僅於列印時顯示（避免畫面重複但列印可見）。
            --}}
            <template x-if="activeEvents.length">
                <div
                    :class="countdownEvent
                        ? 'order-last md:order-first md:w-2/3'
                        : 'order-first md:w-full'"
                    class="w-full print:w-full"
                >
                    <div class="space-y-2">
                        <template
                            x-for="event in activeEvents"
                            :key="event.start + event.name"
                        >
                            <div
                                :class="isCountdownMatch(event)
                                    ? 'hidden print:flex'
                                    : 'flex'"
                                class="flex-col-reverse items-start justify-between gap-x-2 gap-y-1 border-b border-warm-100 py-2 last:border-0 sm:flex-row sm:items-center dark:border-zinc-800"
                            >
                                <span
                                    class="font-medium text-warm-800 dark:text-zinc-200"
                                    x-text="event.name"
                                ></span>
                                <div
                                    class="flex flex-col-reverse items-start gap-x-2 text-sm text-warm-600 tabular-nums sm:flex-row sm:items-center dark:text-zinc-400"
                                >
                                    <template x-if="event.status === 'ongoing'">
                                        <span
                                            class="inline-flex shrink-0 items-center rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 print:hidden"
                                        >
                                            進行中
                                        </span>
                                    </template>

                                    <span
                                        class="shrink-0"
                                        x-text="shortDateRange(event)"
                                    ></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <template x-if="showTaipeiHint">
            <p
                class="mt-3 text-xs text-warm-500 dark:text-zinc-400 print:hidden"
            >此區塊日期皆為台灣時間（Asia/Taipei）</p>
        </template>
    </x-card>
@endif
