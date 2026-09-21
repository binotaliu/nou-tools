{{-- Printable schedule sheet: A4 landscape, rendered to PDF by Browsershot
(RenderSchedulePdf) and previewable as plain HTML at the same URL minus `.pdf`.

The left strip is the cut-off exam slip; the right part is the semester
calendar plus a QR code back to the web schedule.

$inlineAssets: the PDF renders this view as an HTML string with no origin, so
the built CSS is inlined instead of linked (needs `npm run build`). --}}
<!DOCTYPE html>
<html lang="zh-hant">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>{{ $page->name }} - {{ $page->semesterLabel }} 列印課表</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=block"
    />
    @if ($inlineAssets)
        {{-- No CSP nonce: the PDF has no CSP header, and a per-request nonce would change the HTML and defeat RenderSchedulePdf's cache. --}}
        <style>
            {!! \Illuminate\Support\Facades\Vite::content('resources/css/schedule-print.css') !!}
        </style>
    @else
        @vite('resources/css/schedule-print.css')
    @endif
</head>
@php
    // Above 9 courses the course list goes two columns (credits as bare
    // numbers) to leave the exam table its height.
    $manyCourses = count($page->courses) > 9;
@endphp
<body class="m-0 font-sans text-zinc-900">
    <main
        class="mx-auto grid h-[210mm] w-[297mm] grid-cols-[96mm_1fr] overflow-hidden bg-white"
    >
        {{-- Left: the strip to cut off and bring to the exam. --}}
        <section
            class="flex min-h-0 flex-col gap-3 border-r border-dashed border-zinc-500 p-[8mm]"
        >
            @include('schedule.print._header')

            <div>
                <h1 class="text-[11pt] leading-tight font-bold">
                    {{ $page->name }}
                </h1>
                <p class="text-[8pt] text-zinc-500">{{ $page->semesterLabel }}</p>
            </div>

            <div>
                <h2
                    class="mb-1 flex items-baseline justify-between border-b border-zinc-900 pb-0.5 text-[9pt] font-bold"
                >
                    <span>本學期課程</span>
                    @if ($manyCourses)
                        <span class="text-[7pt] font-normal text-zinc-500"
                            >數字為學分</span
                        >
                    @endif
                </h2>
                <div @class(['grid gap-x-3', 'grid-cols-2' => $manyCourses])>
                    @forelse ($page->courses as $course)
                        <p class="flex items-baseline justify-between gap-1.5 py-px text-[8.5pt] leading-tight">
                            <span>{{ $course->name }}</span>
                            <span class="shrink-0 text-zinc-500 tabular-nums">
                                @if ($course->credits === null)
                                    —
                                @else
                                    {{ $course->credits }}{{ $manyCourses ? '' : ' 學分' }}
                                @endif
                            </span>
                        </p>
                    @empty
                        <p class="text-[8.5pt] text-zinc-400">此學期尚無課程</p>
                    @endforelse
                </div>
            </div>

            <div class="min-h-0 flex-1">
                <h2
                    class="mb-1 border-b border-zinc-900 pb-0.5 text-[9pt] font-bold"
                >
                    考試時間表
                </h2>

                @include('schedule.print._exam-table', ['exams' => $page->exams, 'hasMidterm' => $page->hasMidterm, 'dense' => count($page->exams) > 9])
            </div>
        </section>

        {{-- Right: semester calendar and the way back to the web schedule. --}}
        <section class="flex min-h-0 flex-col gap-3 p-[8mm]">
            <div class="flex items-center justify-between gap-4">
                @include('schedule.print._header')
                <div class="flex items-center gap-4 text-[7.5pt] text-zinc-600">
                    <p class="flex items-center gap-1.5">
                        <span class="size-2.5 rounded-full bg-zinc-900"></span>
                        有視訊面授
                    </p>
                    <p class="flex items-center gap-1.5">
                        <span
                            class="w-2.5 text-center font-bold text-zinc-900 underline decoration-2 underline-offset-2"
                            >9</span
                        >
                        期中／期末考
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[11pt] leading-tight font-bold">{{ $page->name }}</p>
                    <p class="text-[8pt] text-zinc-500">{{ $page->semesterLabel }}</p>
                </div>
            </div>

            {{-- The QR code takes the empty cell after the last month when there
            is one (a 2-row grid of 4 or 5 months); a full last row pushes it
            below the calendars instead. Three columns is the norm; a term with
            long per-date course lists gets four so it still fits the page
            (ResolvePrintMonthColumns). --}}
            @php
                $monthColumns = $page->monthColumns;
                $qrInGrid = count($page->months) % $monthColumns !== 0;
                // Months span 4-6 week rows; pad them all to the tallest one so
                // the course lists underneath start at the same height.
                $weekRows = max(array_map(fn ($month) => count($month->weeks), $page->months) ?: [0]);
            @endphp
            <div class="relative flex-1">
                <div
                    @class([
                        'grid content-start gap-x-6 gap-y-3',
                        'grid-cols-3' => $monthColumns === 3,
                        'grid-cols-4' => $monthColumns === 4,
                    ])
                >
                    @foreach ($page->months as $month)
                        @include('schedule.print._month', ['month' => $month, 'weekRows' => $weekRows, 'weekdayLabels' => $page->weekdayLabels])
                    @endforeach
                </div>

                @if ($qrInGrid)
                    <div class="absolute right-0 bottom-0">
                        @include('schedule.print._qr')
                    </div>
                @endif
            </div>

            @unless ($qrInGrid)
                <div class="self-end">
                    @include('schedule.print._qr')
                </div>
            @endunless
        </section>
    </main>
</body>
</html>
