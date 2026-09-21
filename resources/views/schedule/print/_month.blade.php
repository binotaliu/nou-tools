{{-- A month grid, Monday first. A class date is a filled dark circle with a white
number so it survives a black-and-white printer. Under the grid, each class date
lists its courses and start times, so the sheet works without the QR code. --}}
<div class="break-inside-avoid">
    <p class="mb-1 text-[11pt] font-bold text-theme-900">{{ $month->title }}</p>
    <div class="grid grid-cols-7 text-center text-[8pt]">
        @foreach (['一', '二', '三', '四', '五', '六', '日'] as $weekday)
            <span
                class="border-b border-zinc-300 pb-0.5 text-zinc-500"
                >{{ $weekday }}</span
            >
        @endforeach

        @foreach ($month->weeks as $week)
            @foreach ($week as $cell)
                <span class="flex h-[5.2mm] items-center justify-center">
                    @if ($cell)
                        <span
                            @class([
                                'flex size-[4.6mm] items-center justify-center rounded-full tabular-nums',
                                'bg-zinc-900 font-bold text-white' => $cell['hasClass'],
                                'text-zinc-600' => ! $cell['hasClass'],
                            ])
                            >{{ $cell['day'] }}</span
                        >
                    @endif
                </span>
            @endforeach
        @endforeach
    </div>

    @if ($month->classDays !== [])
        <ul
            class="mt-1 space-y-px border-t border-zinc-300 pt-1 text-[7pt] leading-tight text-zinc-700"
        >
            @foreach ($month->classDays as $day)
                <li class="flex gap-1.5">
                    <span
                        class="w-[13mm] shrink-0 font-bold tabular-nums"
                        >{{ $day['label'] }}</span
                    >
                    <span class="min-w-0">
                        @foreach ($day['courses'] as $course)
                            {{ $course['name'] }}
                            @if ($course['time'])
                                <span
                                    class="text-zinc-500 tabular-nums"
                                    >{{ $course['time'] }}</span
                                >
                            @endif
                            {{ $loop->last ? '' : '、' }}
                        @endforeach
                    </span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-1 border-t border-zinc-300 pt-1 text-[7.5pt] text-zinc-400">本月沒有視訊面授</p>
    @endif
</div>
