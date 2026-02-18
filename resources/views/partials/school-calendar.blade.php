{{-- School Schedule Calendar Component --}}
@if(!empty($scheduleEvents) || $countdownEvent)
    <div class="bg-white p-6 rounded-lg border border-warm-200">
        <h3 class="text-lg font-medium mb-4">學校行事曆</h3>

        <div class="flex flex-col md:flex-row md:items-start md:gap-6">
            {{-- Countdown (mobile 上方，桌面右側 1/3) --}}
            @if($countdownEvent)
                <div class="w-full @if(!empty($scheduleEvents)) order-first md:order-last md:w-1/3 @else order-first md:w-full @endif">
                    <div class="mb-4 p-4 bg-warm-50 border border-warm-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-warm-800">{{ $countdownEvent['name'] }}</h4>
                                <p class="text-sm text-warm-600 mt-1">
                                    {{ $countdownEvent['start']->format('Y年m月d日') }}
                                    @if($countdownEvent['start']->format('Y-m-d') !== $countdownEvent['end']->format('Y-m-d'))
                                        - {{ $countdownEvent['end']->format('m月d日') }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                @if($countdownEvent['status'] === 'ongoing')
                                    <div class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 mr-1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
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

            {{-- Schedule Events (手機在 countdown 之下，桌面佔 2/3) --}}
            @php
                // 當 countdownEvent 存在時，從列表中排除該項目，避免在倒數區塊與列表中重複顯示。
                $eventsToShow = $scheduleEvents ?? [];
                if ($countdownEvent) {
                    $eventsToShow = array_values(array_filter($eventsToShow, function ($e) use ($countdownEvent) {
                        // 以 name + start date 精準比對並排除相同的倒數事件
                        return !(
                            $e['name'] === $countdownEvent['name']
                            && $e['start']->format('Y-m-d') === $countdownEvent['start']->format('Y-m-d')
                        );
                    }));
                }
            @endphp

            @if(!empty($eventsToShow))
                <div class="w-full @if($countdownEvent) order-last md:order-first md:w-2/3 @else order-first md:w-full @endif">
                    <div class="space-y-2">
                        @foreach($eventsToShow as $event)
                            <div class="flex items-center justify-between py-2 border-b border-warm-100 last:border-0">
                                <span class="font-medium text-warm-800">{{ $event['name'] }}</span>
                                <div class="flex items-center gap-2 text-sm text-warm-600">
                                    @if($event['status'] === 'ongoing')
                                        <span class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs font-medium">
                                            進行中
                                        </span>
                                    @endif
                                    <span>
                                        {{ $event['start']->format('m月d日') }}
                                        @if($event['start']->format('Y-m-d') !== $event['end']->format('Y-m-d'))
                                            - {{ $event['end']->format('m月d日') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
