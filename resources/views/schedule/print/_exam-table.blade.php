{{-- Exam table, one row per course. Midterm and final share a time slot (only
the dates differ), so the time appears once and the dates sit under the weekday
they fall on. The 班級代碼 field is once per course: the exam room asks for it,
and the class sat isn't always the one on the schedule.

$hasMidterm: summer terms have no midterm, so no 期中教室 field.
$dense: tighter rows when the semester has many courses, so the strip still
fits on the page. --}}
<div class="text-[8.5pt]">
    <div
        class="grid grid-cols-[1fr_15mm_15mm] items-center bg-theme-700 px-1.5 py-0.5 font-bold text-white"
    >
        <span>課程・時間</span>
        <span class="text-center">週六</span>
        <span class="text-center">週日</span>
    </div>

    @forelse ($exams as $exam)
        <div
            @class(['break-inside-avoid border-b border-zinc-300 px-1.5', 'py-1' => ! $dense, 'py-0.5' => $dense])
        >
            <div class="grid grid-cols-[1fr_15mm_15mm] items-start gap-y-px">
                <div class="min-w-0 pr-1">
                    <p class="leading-tight font-semibold">{{ $exam->courseName }}</p>
                    @if ($exam->time)
                        <p class="text-[7pt] leading-tight text-zinc-500 tabular-nums">{{ $exam->time }}</p>
                    @endif
                </div>

                @foreach ([$exam->saturday, $exam->sunday] as $dates)
                    <div
                        class="text-center text-[7.5pt] leading-tight tabular-nums"
                    >
                        @foreach ($dates as $date)
                            <p><span class="text-zinc-500">{{ $date['kind'] === '期中考' ? '期中' : '期末' }}</span> {{ $date['label'] }}</p>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @foreach ($exam->other as $date)
                <p class="text-[7pt] leading-tight text-zinc-500">
                    另有{{ $date['kind'] === '期中考' ? '期中' : '期末' }} {{ $date['label'] }}
                </p>
            @endforeach

            {{-- Write-in fields: the exam room asks for the 班級代碼, and the classroom
            is announced separately for each exam. --}}
            <div
                @class(['mt-0.5 grid gap-x-2', 'grid-cols-3' => $hasMidterm, 'grid-cols-2' => ! $hasMidterm])
            >
                @foreach (array_filter(['班級代碼', $hasMidterm ? '期中教室' : null, '期末教室']) as $label)
                    <div class="flex items-end gap-1 text-[7pt] text-zinc-500">
                        <span class="shrink-0">{{ $label }}</span>
                        <span
                            @class(['min-w-0 flex-1 border-b border-zinc-800', 'h-3' => ! $dense, 'h-2.5' => $dense])
                        ></span>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="py-2 text-center text-zinc-400">尚無考試資訊</p>
    @endforelse
</div>
