{{-- School Schedule Calendar Component --}}
@if (! empty($scheduleEvents) || $countdownEvent)
    <x-card {{ $attributes->merge(['title' => '學校行事曆']) }}>
        <div class="flex flex-col md:flex-row md:items-start md:gap-6">
            {{-- Countdown (mobile 上方，桌面右側 1/3) --}}
            @if ($countdownEvent)
                <div
                    @class([
                        'w-full print:hidden',
                        'order-first md:order-last md:w-1/3' => ! empty($scheduleEvents),
                        'order-first md:w-full' => empty($scheduleEvents),
                    ])
                >
                    <div
                        class="mb-4 rounded-lg border border-warm-200 bg-warm-50 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-warm-800">
                                    {{ $countdownEvent['name'] }}
                                </div>
                                <p
                                    class="mt-1 text-sm text-warm-600 tabular-nums"
                                >
                                    {{ $countdownEvent['start']->format('Y 年 n 月 j 日') }}
                                    @if ($countdownEvent['start']->format('Y-m-d') !== $countdownEvent['end']->format('Y-m-d'))
                                        –
                                        {{ $countdownEvent['end']->format('n 月 j 日') }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                @if ($countdownEvent['status'] === 'ongoing')
                                    <div
                                        class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800"
                                    >
                                        進行中
                                    </div>
                                @else
                                    <div
                                        class="text-3xl font-bold text-warm-700"
                                    >
                                        {{ $countdownEvent['daysUntil'] }}
                                    </div>
                                    <div class="text-sm text-warm-500">
                                        天後
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{--
                Schedule Events (手機在 countdown 之下，桌面佔 2/3)
                當有 countdownEvent 時：不從列表移除該項目；在渲染時把該行於畫面上隱藏、僅於列印時顯示（避免畫面重複但列印可見）。
            --}}
            @php
                $eventsToShow = $scheduleEvents ?? [];
            @endphp

            @if (! empty($eventsToShow))
                <div
                    @class([
                        'w-full print:w-full',
                        'order-last md:order-first md:w-2/3' => $countdownEvent,
                        'order-first md:w-full' => ! $countdownEvent,
                    ])
                >
                    <div class="space-y-2">
                        @foreach ($eventsToShow as $event)
                            @php
                                $isCountdownMatch =
                                    $countdownEvent &&
                                    $event['name'] === $countdownEvent['name'] &&
                                    $event['start']->format('Y-m-d') === $countdownEvent['start']->format('Y-m-d');
                            @endphp

                            <div
                                @class([
                                    'items-center justify-between border-b border-warm-100 py-2 last:border-0',
                                    'hidden print:flex' => $isCountdownMatch,
                                    'flex' => ! $isCountdownMatch,
                                ])
                            >
                                <span class="font-medium text-warm-800">
                                    {{ $event['name'] }}
                                </span>
                                <div
                                    class="flex items-center gap-2 text-sm text-warm-600 tabular-nums"
                                >
                                    @if ($event['status'] === 'ongoing')
                                        <span
                                            class="inline-flex items-center rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 print:hidden"
                                        >
                                            進行中
                                        </span>
                                    @endif

                                    <span>
                                        {{ $event['start']->format('n 月 j 日') }}
                                        @if ($event['start']->format('Y-m-d') !== $event['end']->format('Y-m-d'))
                                            –
                                            {{ $event['end']->format('n 月 j 日') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-card>
@endif
