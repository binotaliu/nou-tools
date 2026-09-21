{{-- $compact: shrink the cells when a long semester needs more than two rows. A month grid, Monday first. A class date is a filled dark circle with a
white number so it survives a black-and-white printer. --}}
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
                <span
                    @class(['flex items-center justify-center', 'h-[5.6mm]' => $compact, 'h-[6.8mm]' => ! $compact])
                >
                    @if ($cell)
                        <span
                            @class([
                                'flex items-center justify-center rounded-full tabular-nums',
                                'size-[4.8mm]' => $compact,
                                'size-[5.8mm]' => ! $compact,
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
</div>
