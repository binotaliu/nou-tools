@props(['scheduleEvents' => [], 'countdownEvent' => null])

{{-- School Schedule Calendar Component --}}
@if(!empty($scheduleEvents) || $countdownEvent)
    <x-card {{ $attributes }}>
        <h3 class="text-lg font-medium mb-4">學校行事曆</h3>

        <div class="flex flex-col md:flex-row md:items-start md:gap-6">
            {{-- Countdown (mobile 上方，桌面右側 1/3) --}}
            @if($countdownEvent)
                <div class="w-full print:hidden @if(!empty($scheduleEvents)) order-first md:order-last md:w-1/3 @else order-first md:w-full @endif">
                    <div class="mb-4 p-4 bg-warm-50 border border-warm-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-warm-800">{{ $countdownEvent['name'] }}</h4>
                                <p class="text-sm text-warm-600 mt-1 tabular-nums">
                                    {{ $countdownEvent['start']->format('Y 年 n 月 j 日') }}
                                    @if($countdownEvent['start']->format('Y-m-d') !== $countdownEvent['end']->format('Y-m-d'))
                                        – {{ $countdownEvent['end']->format('n 月 j 日') }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                @if($countdownEvent['status'] === 'ongoing')
                                    <div class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                        進行中
                                    </div>
                                @else
                                    <div class="text-3xl font-bold text-warm-700">
                                        {{ $countdownEvent['daysUntil'] }}
                                    </div>
                                    <div class="text-sm text-warm-500">天後</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Schedule Events (手機在 countdown 之下，桌面佔 2/3)
                 當有 countdownEvent 時：不從列表移除該項目；在渲染時把該行於畫面上隱藏、僅於列印時顯示（避免畫面重複但列印可見）。 --}}
            @php
                $eventsToShow = $scheduleEvents ?? [];
            @endphp

            @if(!empty($eventsToShow))
                <div class="w-full print:w-full @if($countdownEvent) order-last md:order-first md:w-2/3 @else order-first md:w-full @endif">
                    <div class="space-y-2">
                        @foreach($eventsToShow as $event)
                            @php
                                $isCountdownMatch = $countdownEvent
                                    && $event['name'] === $countdownEvent['name']
                                    && $event['start']->format('Y-m-d') === $countdownEvent['start']->format('Y-m-d');
                            @endphp
                            <div class="@if($isCountdownMatch) hidden print:flex @else flex @endif items-center justify-between py-2 border-b border-warm-100 last:border-0">
                                <span class="font-medium text-warm-800">{{ $event['name'] }}</span>
                                <div class="flex items-center gap-2 text-sm text-warm-600 tabular-nums">
                                    @if($event['status'] === 'ongoing')
                                        <span class="print:hidden inline-flex items-center px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs font-medium">
                                            進行中
                                        </span>
                                    @endif
                                    <span>
                                        {{ $event['start']->format('n 月 j 日') }}
                                        @if($event['start']->format('Y-m-d') !== $event['end']->format('Y-m-d'))
                                            – {{ $event['end']->format('n 月 j 日') }}
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
