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
        <style @cspNonce>
            {!! \Illuminate\Support\Facades\Vite::content('resources/css/schedule-print.css') !!}
        </style>
    @else
        @vite('resources/css/schedule-print.css')
    @endif
</head>
<body class="m-0 font-sans text-zinc-900">
    <main
        class="mx-auto grid h-[210mm] w-[297mm] grid-cols-[96mm_1fr] overflow-hidden bg-white"
    >
        {{-- Left: the strip to cut off and bring to the exam. --}}
        <section
            class="relative flex min-h-0 flex-col gap-3 border-r border-dashed border-zinc-500 p-[8mm]"
        >
            <span
                class="absolute top-1/2 -right-[2.6mm] -translate-y-1/2 bg-white text-[9pt] leading-none text-zinc-500"
                >✂</span
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
                    class="mb-1 border-b border-zinc-900 pb-0.5 text-[9pt] font-bold"
                >
                    本學期課程
                </h2>
                @forelse ($page->courses as $course)
                    <p class="flex items-baseline justify-between gap-2 py-px text-[8.5pt] leading-tight">
                        <span>{{ $course->name }}</span>
                        <span
                            class="shrink-0 text-zinc-500 tabular-nums"
                            >{{ $course->credits !== null ? $course->credits.' 學分' : '—' }}</span
                        >
                    </p>
                @empty
                    <p class="text-[8.5pt] text-zinc-400">此學期尚無課程</p>
                @endforelse
            </div>

            <div class="min-h-0 flex-1">
                <h2
                    class="mb-1 border-b border-zinc-900 pb-0.5 text-[9pt] font-bold"
                >
                    考試時間表
                </h2>

                <div class="grid grid-cols-2 gap-x-2">
                    @include('schedule.print._exam-column', ['title' => '週六', 'days' => $page->saturdayExams])
                    @include('schedule.print._exam-column', ['title' => '週日', 'days' => $page->sundayExams])
                </div>

                @if ($page->otherExams !== [])
                    <div class="mt-2">
                        @include('schedule.print._exam-column', ['title' => '其他日期', 'days' => $page->otherExams])
                    </div>
                @endif

                @if ($page->undatedCourseNames !== [])
                    <p class="mt-2 text-[7.5pt] text-zinc-500">
                        考試日期未公布：{{ implode('、', $page->undatedCourseNames) }}
                    </p>
                @endif
            </div>
        </section>

        {{-- Right: semester calendar and the way back to the web schedule. --}}
        <section class="flex min-h-0 flex-col gap-3 p-[8mm]">
            <div class="flex items-center justify-between gap-4">
                @include('schedule.print._header')
                <p class="flex items-center gap-1.5 text-[8pt] text-zinc-600">
                    <span class="size-2.5 rounded-full bg-zinc-900"></span>
                    有視訊面授
                </p>
            </div>

            <div
                @class([
                'grid flex-1 content-start gap-x-6 gap-y-3',
                'grid-cols-3' => count($page->months) <= 6,
                'grid-cols-4' => count($page->months) > 6,
            ])
            >
                @foreach ($page->months as $month)
                    @include('schedule.print._month', ['month' => $month, 'compact' => count($page->months) > 6])
                @endforeach
            </div>

            <div class="flex items-center gap-4 border-t border-zinc-300 pt-3">
                <div class="[&_svg]:h-full [&_svg]:w-full size-[26mm] shrink-0">
                    {!! $page->qrCodeSvg !!}
                </div>
                <div class="min-w-0">
                    <p class="text-[10pt] font-bold">掃描 QR Code，開啟線上課表</p>
                    <p class="text-[8.5pt] text-zinc-600">在線上課表可查看每堂課的時間，並進入視訊教室上課。</p>
                    <p class="mt-1 text-[7.5pt] break-all text-zinc-500">{{ $page->shareUrl }}</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
