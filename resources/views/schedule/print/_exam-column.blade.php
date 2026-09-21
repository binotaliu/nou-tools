{{-- One weekday column of the exam table, grouped by date. Each course gets a
write-in line for its 班級代碼, since the exam room asks for it and the class
isn't always the one on the schedule. Kept to two lines per course so a full
semester (every course with a midterm and a final) still fits the strip. --}}
<div class="min-w-0">
    <p class="bg-theme-700 px-1.5 py-0.5 text-center text-[9pt] font-bold text-white">{{ $title }}</p>

    @forelse ($days as $day)
        <div class="mt-1">
            <p class="bg-zinc-100 px-1.5 py-px text-[8pt] font-bold">{{ $day->dateLabel }}</p>
            @foreach ($day->entries as $entry)
                <div
                    class="break-inside-avoid border-b border-zinc-300 px-1.5 py-0.5"
                >
                    <p class="text-[8pt] leading-tight">
                        <span
                            class="font-semibold"
                            >{{ $entry['courseName'] }}</span
                        >
                    </p>
                    <p class="text-[6.5pt] leading-tight text-zinc-500 tabular-nums">
                        {{ $entry['kind'] }}
                        @if ($entry['time']) {{ $entry['time'] }}@endif
                    </p>
                    <div
                        class="mt-0.5 flex items-end gap-1 text-[6.5pt] text-zinc-500"
                    >
                        <span class="shrink-0">班級代碼</span>
                        <span
                            class="h-3 flex-1 border-b border-zinc-800"
                        ></span>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <p class="mt-2 text-center text-[8pt] text-zinc-400">—</p>
    @endforelse
</div>
