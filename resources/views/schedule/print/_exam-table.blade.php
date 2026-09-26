{{-- Exam table, one row per course. The dates are on the calendars, so the
table only says which weekday a course sits on: its exam time goes in the 週六
and/or 週日 cell (midterm and final share a time slot, so it is the same time
either way). The 班級代碼 field is once per course: the exam room asks for it,
and the class sat isn't always the one on the schedule.

$hasMidterm: summer terms have no midterm, so no 期中教室 field.
$dense: tighter rows when there are many exam rows, so up to 14 courses still
fit on the strip. --}}
<div
    role="table"
    aria-label="考試時間表"
    @class(['text-[8.5pt]' => ! $dense, 'text-[8pt]' => $dense])
>
    <div
        role="row"
        class="grid grid-cols-[1fr_18mm_18mm] items-center bg-theme-700 px-1.5 py-0.5 font-bold text-white"
    >
        <span role="columnheader">課程</span>
        <span role="columnheader" class="text-center">週六</span>
        <span role="columnheader" class="text-center">週日</span>
    </div>

    @forelse ($exams as $exam)
        <div
            role="row"
            @class(['break-inside-avoid border-b border-zinc-300 px-1.5', 'py-1' => ! $dense, 'py-px' => $dense])
        >
            <div class="grid grid-cols-[1fr_18mm_18mm] items-start gap-y-px">
                <p role="rowheader" class="min-w-0 pr-1 leading-tight font-semibold">{{ $exam->courseName }}</p>

                @foreach ([$exam->saturday, $exam->sunday] as $dates)
                    <p role="cell" class="text-center text-[7.5pt] leading-tight tabular-nums">
                        @if ($dates !== [])
                            {{ $exam->time ? str_replace(' ', '', $exam->time) : '時間未定' }}
                        @endif
                    </p>
                @endforeach
            </div>

            {{-- Weekday exams have no column, so the date is the only way to say
            when. This is bad data, so it is rare. --}}
            @foreach ($exam->other as $date)
                <p class="text-[7pt] leading-tight text-zinc-500">
                    另有{{ $date['kind'] === '期中考' ? '期中' : '期末' }} {{ $date['label'] }}{{ $exam->time ? ' '.str_replace(' ', '', $exam->time) : '' }}
                </p>
            @endforeach

            {{-- Write-in fields: the exam room asks for the 班級代碼, and the classroom
            is announced separately for each exam. --}}
            <div
                @class(['grid gap-x-2', 'mt-2 mb-0.5' => ! $dense, 'grid-cols-3' => $hasMidterm, 'grid-cols-2' => ! $hasMidterm])
            >
                @foreach (array_filter(['考試班級', $hasMidterm ? '期中教室' : null, '期末教室']) as $label)
                    <div class="flex items-end gap-1 text-[7pt] text-zinc-500">
                        <span class="shrink-0">{{ $label }}</span>
                        <span
                            @class(['min-w-0 flex-1 border-b border-zinc-600', 'h-3' => ! $dense, 'h-2.5' => $dense])
                        ></span>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="py-2 text-center text-zinc-400">尚無考試資訊</p>
    @endforelse
</div>
