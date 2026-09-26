{{-- A month grid, starting the week on Monday or Sunday ($weekdayLabels). A class date is a filled dark circle with a white
number so it survives a black-and-white printer; an exam date is underlined
(bold when there is no class that day). Under the grid, each class date lists
its courses and start times, so the sheet works without the QR code. --}}
<div class="break-inside-avoid">
    <h3 class="mb-1 text-[11pt] font-bold text-theme-900">
        {{ $month->title }}
    </h3>
    {{-- The dated lists below carry the same information in text. --}}
    <div aria-hidden="true" class="grid grid-cols-7 text-center text-[8pt]">
        @foreach ($weekdayLabels as $weekday)
            <span
                class="border-b border-zinc-300 pb-0.5 text-zinc-500"
                >{{ $weekday }}</span
            >
        @endforeach

        @foreach ($month->weeks as $week)
            @foreach ($week as $cell)
                <span class="flex h-[5mm] items-center justify-center">
                    @if ($cell)
                        <span
                            @class([
                                'flex size-[4.4mm] items-center justify-center tabular-nums',
                                'rounded-full',
                                'bg-zinc-900 font-bold text-white' => $cell['hasClass'],
                                'font-bold text-zinc-900' => $cell['isExam'] && ! $cell['hasClass'],
                                'text-zinc-600' => ! $cell['hasClass'] && ! $cell['isExam'],
                                'underline decoration-2 underline-offset-2' => $cell['isExam'],
                            ])
                            >{{ $cell['day'] }}</span
                        >
                    @endif
                </span>
            @endforeach
        @endforeach

        @for ($blank = count($month->weeks) * 7; $blank < $weekRows * 7; $blank++)
            <span class="h-[5mm]"></span>
        @endfor
    </div>

    @if ($month->classDays !== [] || $month->examDays !== [])
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
                            @if (! $loop->last)
                                <br />
                            @endif
                        @endforeach
                    </span>
                </li>
            @endforeach
            @foreach ($month->examDays as $day)
                <li class="flex gap-1.5">
                    <span
                        class="w-[13mm] shrink-0 font-bold tabular-nums"
                        >{{ $day['label'] }}</span
                    >
                    <span class="min-w-0">
                        <span class="mr-0.5 font-bold">{{ $day['kind'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-1 border-t border-zinc-300 pt-1 text-[7.5pt] text-zinc-400">本月沒有視訊面授或考試</p>
    @endif
</div>
