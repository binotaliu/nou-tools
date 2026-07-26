@push('head')
    <x-json-ld
        :data="array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $viewModel->course->name,
            'courseCode' => (string) $viewModel->course->id,
            'provider' => [
                '@type' => 'CollegeOrUniversity',
                'name' => '國立空中大學',
            ],
            'url' => url()->current(),
            'inLanguage' => 'zh-Hant',
        ])"
    />
@endpush

<x-layout :title="$viewModel->course->name . ' - 檢視課程 - NOU 小幫手'">
    <div class="mx-auto max-w-5xl">
        <div class="mb-8">
            <x-link-button
                :href="isset($viewModel->previousSchedule) ? route('schedules.show', $viewModel->previousSchedule->token) : url()->previous()"
                variant="text-link"
                class="mb-4"
            >
                <x-heroicon-o-chevron-left class="size-4" />
                回到我的課表
            </x-link-button>
            <h2
                class="mb-2 text-3xl font-bold text-warm-900 dark:text-zinc-100"
            >
                {{ $viewModel->course->name }}
            </h2>

            @if (! empty($viewModel->course->term))
                <div class="mb-4 text-sm text-warm-600 dark:text-zinc-400">
                    {{ \Illuminate\Support\Str::toSemesterDisplay($viewModel->course->term) }}
                </div>
            @endif
        </div>

        {{-- Course Information --}}
        <x-card class="mb-6" title="課程資訊">
            <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- 科目內容 --}}
                @if ($viewModel->course->descriptionUrl)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            科目內容
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            <x-link-button
                                :href="$viewModel->course->descriptionUrl"
                                variant="link"
                                target="_blank"
                                rel="noopener"
                                data-analytics-event="course_description_open"
                                data-analytics-feature="course"
                            >
                                檢視詳細內容
                                <x-heroicon-o-arrow-top-right-on-square
                                    class="size-4"
                                />
                            </x-link-button>
                        </dd>
                    </div>
                @endif

                {{-- 必/選修 --}}
                @if ($viewModel->course->creditType)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            必/選修
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            {{ $viewModel->course->creditType }}
                        </dd>
                    </div>
                @endif

                {{-- 學分 --}}
                @if ($viewModel->course->credits)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            學分
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center gap-1 text-orange-500"
                                    aria-hidden="true"
                                >
                                    @php
                                        $starCount = (int) floor($viewModel->course->credits);
                                        $displayStars = min($starCount, 6);
                                    @endphp

                                    @for ($i = 0; $i < $displayStars; $i++)
                                        <x-heroicon-s-star class="size-4" />
                                    @endfor

                                    @if ($starCount > $displayStars)
                                        <span
                                            class="text-xs text-warm-600 dark:text-zinc-400"
                                        >
                                            +{{ $starCount - $displayStars }}
                                        </span>
                                    @endif
                                </div>

                                <div
                                    class="text-sm text-warm-600 dark:text-zinc-400"
                                >
                                    {{ $viewModel->course->credits }} 學分
                                </div>
                            </div>
                        </dd>
                    </div>
                @endif

                {{-- 學系 --}}
                @if ($viewModel->course->department)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            學系
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            {{ $viewModel->course->department }}
                        </dd>
                    </div>
                @endif

                {{-- 面授類別 --}}
                @if ($viewModel->inPersonClassType)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            面授類別
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            {{ $viewModel->inPersonClassType }}
                        </dd>
                    </div>
                @endif

                {{-- 媒體 --}}
                @if ($viewModel->media)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            媒體
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            {{ $viewModel->media }}
                        </dd>
                    </div>
                @endif

                {{-- 多媒體簡介 --}}
                @if ($viewModel->multimediaUrl)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            多媒體簡介
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            <x-link-button
                                :href="$viewModel->multimediaUrl"
                                variant="link"
                                target="_blank"
                                rel="noopener"
                            >
                                檢視簡介
                                <x-heroicon-o-arrow-top-right-on-square
                                    class="size-4"
                                />
                            </x-link-button>
                        </dd>
                    </div>
                @endif

                {{-- 課程性質 --}}
                @if ($viewModel->course->nature)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            課程性質
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            {{ $viewModel->course->nature }}
                        </dd>
                    </div>
                @endif

                @if ($viewModel->course->midtermDate || $viewModel->course->finalDate || $viewModel->course->examTimeStart || $viewModel->course->examTimeEnd)
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            考試資訊
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            @if ($viewModel->course->midtermDate)
                                <div class="mb-2">
                                    <div class="font-semibold">期中考</div>
                                    <div
                                        class="flex items-center justify-start gap-x-2 text-sm text-warm-700 tabular-nums dark:text-zinc-300"
                                    >
                                        <div>
                                            {{ Date::parse($viewModel->course->midtermDate)->isoFormat('M/D (dd)') }}
                                        </div>

                                        @if ($viewModel->course->examTimeStart || $viewModel->course->examTimeEnd)
                                            <div
                                                class="text-sm whitespace-nowrap text-warm-600 dark:text-zinc-400"
                                            >
                                                @if ($viewModel->course->examTimeStart && $viewModel->course->examTimeEnd)
                                                    {{ $viewModel->course->examTimeStart }}
                                                    -
                                                    {{ $viewModel->course->examTimeEnd }}
                                                @else
                                                    {{ $viewModel->course->examTimeStart ?? $viewModel->course->examTimeEnd }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($viewModel->course->finalDate)
                                <div>
                                    <div class="font-semibold">期末考</div>
                                    <div
                                        class="flex items-center justify-start gap-x-2 text-sm text-warm-700 tabular-nums dark:text-zinc-300"
                                    >
                                        <div>
                                            {{ Date::parse($viewModel->course->finalDate)->isoFormat('M/D (dd)') }}
                                        </div>

                                        @if ($viewModel->course->examTimeStart || $viewModel->course->examTimeEnd)
                                            <div
                                                class="text-sm whitespace-nowrap text-warm-600 dark:text-zinc-400"
                                            >
                                                @if ($viewModel->course->examTimeStart && $viewModel->course->examTimeEnd)
                                                    {{ $viewModel->course->examTimeStart }}
                                                    -
                                                    {{ $viewModel->course->examTimeEnd }}
                                                @else
                                                    {{ $viewModel->course->examTimeStart ?? $viewModel->course->examTimeEnd }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </x-card>

        {{-- Course Classes --}}
        {{-- 教科書資訊 --}}
        @if ($viewModel->course->textbook !== null)
            <x-card class="mb-6" title="教科書資訊">
                <dl
                    class="grid grid-cols-1 gap-6 text-warm-700 md:grid-cols-2 dark:text-zinc-300"
                >
                    <div>
                        <dt
                            class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                        >
                            書名
                        </dt>
                        <dd class="text-warm-700 dark:text-zinc-300">
                            {{ $viewModel->course->textbook->bookTitle }}
                        </dd>
                    </div>

                    @if ($viewModel->course->textbook->edition)
                        <div>
                            <dt
                                class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                            >
                                版本
                            </dt>
                            <dd class="text-warm-700 dark:text-zinc-300">
                                {{ $viewModel->course->textbook->edition }}
                            </dd>
                        </div>
                    @endif

                    @if ($viewModel->course->textbook->priceInfo && is_numeric($viewModel->course->textbook->priceInfo))
                        <div>
                            <dt
                                class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                            >
                                價格
                            </dt>
                            <dd class="text-warm-700 dark:text-zinc-300">
                                ${{ number_format($viewModel->course->textbook->priceInfo) }}
                            </dd>
                        </div>
                    @else($viewModel->course->textbook->priceInfo)
                        <div>
                            <dt
                                class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                            >
                                坊間教科書資訊
                            </dt>
                            <dd class="text-warm-700 dark:text-zinc-300">
                                {{ $viewModel->course->textbook->priceInfo }}
                            </dd>
                        </div>
                    @endif

                    @if ($viewModel->course->textbook->referenceUrl)
                        <div>
                            <dt
                                class="mb-2 font-semibold text-warm-900 dark:text-zinc-100"
                            >
                                參考連結
                            </dt>
                            <dd class="text-warm-700 dark:text-zinc-300">
                                <x-link-button
                                    :href="$viewModel->course->textbook->referenceUrl"
                                    variant="link"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    開啟
                                    <x-heroicon-o-arrow-top-right-on-square
                                        class="size-4"
                                    />
                                </x-link-button>
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        @endif

        @if ($viewModel->course->classes->count() > 0)
            <x-card class="mb-6" title="視訊面授班級與上課時間">
                @php
                    $typeOrder = ['morning', 'afternoon', 'evening', 'full_remote', 'micro_credit', 'computer_lab'];
                    $grouped = $viewModel->course->classes->toCollection()->groupBy(fn ($c) => $c->type->value);
                @endphp

                <div class="space-y-6">
                    @foreach ($typeOrder as $type)
                        @if (isset($grouped[$type]) && $grouped[$type]->isNotEmpty())
                            <div>
                                <div
                                    class="mb-3 font-semibold text-warm-900 dark:text-zinc-100"
                                >
                                    {{ \App\Enums\CourseClassType::tryFrom($type)?->label() ?? $type }}
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                                >
                                    @foreach ($grouped[$type] as $class)
                                        <div
                                            class="rounded-lg border-2 border-warm-200 bg-warm-50 p-4 dark:border-zinc-700 dark:bg-zinc-950"
                                        >
                                            <div class="mb-3">
                                                <div
                                                    class="flex items-start justify-between"
                                                >
                                                    <div>
                                                        <div
                                                            class="font-semibold text-warm-900 dark:text-zinc-100"
                                                        >
                                                            {{ $class->code }}
                                                        </div>
                                                        @if ($class->teacherName)
                                                            <div
                                                                class="mt-1 truncate text-sm text-warm-700 dark:text-zinc-300"
                                                            >
                                                                @php
                                                                    $teacher = $class->teacherName;
                                                                    $suffix = mb_substr($teacher, -2, null, 'UTF-8');
                                                                    $base = mb_substr($teacher, 0, mb_strlen($teacher, 'UTF-8') - 2, 'UTF-8');
                                                                @endphp

                                                                @if ($suffix === '老師')
                                                                    <span
                                                                        class="inline-flex items-baseline gap-0.5"
                                                                    >
                                                                        <span>
                                                                            {{ $base }}
                                                                        </span>
                                                                        <span
                                                                            class="text-xs"
                                                                        >
                                                                            老師
                                                                        </span>
                                                                    </span>
                                                                @else
                                                                    {{ $teacher }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div
                                                        class="text-sm whitespace-nowrap text-warm-600 dark:text-zinc-400"
                                                    >
                                                        @if ($class->startTime)
                                                            <div>
                                                                {{ $class->startTime }}
                                                                -
                                                                {{ $class->endTime }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($class->link || $class->backupClassroomUrl)
                                                <div
                                                    class="mb-3 flex flex-wrap gap-2"
                                                >
                                                    @if ($class->link)
                                                        <a
                                                            href="{{ $class->link }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="inline-flex items-center gap-1 rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-700 transition hover:bg-orange-100 dark:border-orange-800/60 dark:bg-orange-950/60 dark:text-orange-300 dark:hover:bg-orange-950"
                                                        >
                                                            <x-heroicon-o-video-camera
                                                                class="size-4"
                                                            />
                                                            視訊上課
                                                        </a>
                                                    @endif

                                                    @if ($class->backupClassroomUrl)
                                                        <a
                                                            href="{{ $class->backupClassroomUrl }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="inline-flex items-center gap-1 rounded-full border border-warm-200 bg-white px-3 py-1.5 text-sm font-semibold text-warm-700 transition hover:bg-warm-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-900"
                                                        >
                                                            <x-heroicon-o-squares-plus
                                                                class="size-4"
                                                            />
                                                            備用教室
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($class->sessions->count() > 0)
                                                <div
                                                    class="mt-2 rounded bg-white p-3 dark:bg-zinc-900"
                                                >
                                                    <p
                                                        class="mb-2 text-sm font-semibold text-warm-900 dark:text-zinc-100"
                                                    >
                                                        視訊面授日期：
                                                    </p>

                                                    {{-- 列出每一天；只有 session 本身有 startTime/endTime (override) 時，才在該日期旁顯示覆寫時間 --}}
                                                    <div
                                                        class="space-y-1 text-sm text-warm-700 dark:text-zinc-300"
                                                    >
                                                        @php
                                                            $sessionsByDate = $class->sessions
                                                                ->toCollection()
                                                                ->sortBy('date')
                                                                ->groupBy(fn ($s) => $s->date);
                                                        @endphp

                                                        @foreach ($sessionsByDate as $dateKey => $sessions)
                                                            @php
                                                                $s = $sessions->first();
                                                                $d = $s->date;
                                                            @endphp

                                                            <div
                                                                class="flex items-center justify-between tabular-nums"
                                                            >
                                                                <div
                                                                    class="font-semibold"
                                                                >
                                                                    {{ Date::parse($d)->isoFormat('M/D (dd)') }}
                                                                </div>

                                                                @if ($s->startTime || $s->endTime)
                                                                    <div
                                                                        class="text-sm whitespace-nowrap text-warm-600 dark:text-zinc-400"
                                                                    >
                                                                        @if ($s->startTime && $s->endTime)
                                                                            {{ $s->startTime }}
                                                                            -
                                                                            {{ $s->endTime }}
                                                                        @elseif ($s->startTime)
                                                                            {{ $s->startTime }}
                                                                        @else
                                                                            {{ $s->endTime }}
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <p
                                                    class="mt-2 text-sm text-warm-600 dark:text-zinc-400"
                                                >
                                                    未設定上課時間
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-card>
        @endif

        {{-- Previous Exams Section (only shown when user has a schedule cookie) --}}
        @if ($viewModel->previousSchedule && $viewModel->course->previousExams->count() > 0)
            <x-card
                class="mb-6 print:hidden"
                title="考古題"
                id="previous-exams"
            >
                {{-- 手機：卡片列表 --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($viewModel->course->previousExams as $exam)
                        <div
                            class="rounded-lg border border-warm-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <div
                                class="mb-3 font-semibold text-warm-900 dark:text-zinc-100"
                            >
                                {{ $exam->term ?? '-' }}
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p
                                        class="mb-1 font-semibold text-warm-600 dark:text-zinc-400"
                                    >
                                        期中考正參
                                    </p>
                                    @if ($exam->midtermReferencePrimary)
                                        <x-link-button
                                            href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->midtermReferencePrimary }}"
                                            variant="link"
                                            target="_blank"
                                            rel="noopener"
                                            aria-label="{{ $exam->term }}的期中考正參"
                                        >
                                            正參
                                            <x-heroicon-o-arrow-top-right-on-square
                                                class="size-4"
                                            />
                                        </x-link-button>
                                    @else
                                        <span
                                            class="text-warm-500 dark:text-zinc-400"
                                        >
                                            —
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <p
                                        class="mb-1 font-semibold text-warm-600 dark:text-zinc-400"
                                    >
                                        期中考副參
                                    </p>
                                    @if ($exam->midtermReferenceSecondary)
                                        <x-link-button
                                            href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->midtermReferenceSecondary }}"
                                            variant="link"
                                            target="_blank"
                                            rel="noopener"
                                            aria-label="{{ $exam->term }}的期中考副參"
                                        >
                                            副參
                                            <x-heroicon-o-arrow-top-right-on-square
                                                class="size-4"
                                            />
                                        </x-link-button>
                                    @else
                                        <span
                                            class="text-warm-500 dark:text-zinc-400"
                                        >
                                            —
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <p
                                        class="mb-1 font-semibold text-warm-600 dark:text-zinc-400"
                                    >
                                        期末考正參
                                    </p>
                                    @if ($exam->finalReferencePrimary)
                                        <x-link-button
                                            href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->finalReferencePrimary }}"
                                            variant="link"
                                            target="_blank"
                                            rel="noopener"
                                            aria-label="{{ $exam->term }}的期末考正參"
                                        >
                                            正參
                                            <x-heroicon-o-arrow-top-right-on-square
                                                class="size-4"
                                            />
                                        </x-link-button>
                                    @else
                                        <span
                                            class="text-warm-500 dark:text-zinc-400"
                                        >
                                            —
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <p
                                        class="mb-1 font-semibold text-warm-600 dark:text-zinc-400"
                                    >
                                        期末考副參
                                    </p>
                                    @if ($exam->finalReferenceSecondary)
                                        <x-link-button
                                            href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->finalReferenceSecondary }}"
                                            variant="link"
                                            target="_blank"
                                            rel="noopener"
                                            aria-label="{{ $exam->term }}的期末考副參"
                                        >
                                            副參
                                            <x-heroicon-o-arrow-top-right-on-square
                                                class="size-4"
                                            />
                                        </x-link-button>
                                    @else
                                        <span
                                            class="text-warm-500 dark:text-zinc-400"
                                        >
                                            —
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- 桌面：維持表格，但只在 md+ 顯示 --}}
                <div class="hidden overflow-x-auto md:block">
                    <x-table caption="考古題">
                        <x-table-head>
                            <x-table-row>
                                <x-table-head-column class="text-center">
                                    學期
                                </x-table-head-column>
                                <x-table-head-column class="text-center">
                                    期中考正參
                                </x-table-head-column>
                                <x-table-head-column class="text-center">
                                    期中考副參
                                </x-table-head-column>
                                <x-table-head-column class="text-center">
                                    期末考正參
                                </x-table-head-column>
                                <x-table-head-column class="text-center">
                                    期末考副參
                                </x-table-head-column>
                            </x-table-row>
                        </x-table-head>

                        <x-table-body>
                            @foreach ($viewModel->course->previousExams as $exam)
                                <x-table-row>
                                    <x-table-head-column
                                        scope="row"
                                        class="text-center tabular-nums"
                                    >
                                        {{ $exam->term ?? '-' }}
                                    </x-table-head-column>
                                    <x-table-column class="text-center">
                                        @if ($exam->midtermReferencePrimary)
                                            <x-link-button
                                                href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->midtermReferencePrimary }}"
                                                variant="link"
                                                target="_blank"
                                                rel="noopener"
                                                aria-label="{{ $exam->term }}的期中考正參"
                                            >
                                                正參
                                                <x-heroicon-o-arrow-top-right-on-square
                                                    class="size-4"
                                                />
                                            </x-link-button>
                                        @else
                                                —
                                        @endif
                                    </x-table-column>
                                    <x-table-column class="text-center">
                                        @if ($exam->midtermReferenceSecondary)
                                            <x-link-button
                                                href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->midtermReferenceSecondary }}"
                                                variant="link"
                                                target="_blank"
                                                rel="noopener"
                                                aria-label="{{ $exam->term }}的期中考副參"
                                            >
                                                副參
                                                <x-heroicon-o-arrow-top-right-on-square
                                                    class="size-4"
                                                />
                                            </x-link-button>
                                        @else
                                                —
                                        @endif
                                    </x-table-column>
                                    <x-table-column class="text-center">
                                        @if ($exam->finalReferencePrimary)
                                            <x-link-button
                                                href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->finalReferencePrimary }}"
                                                variant="link"
                                                target="_blank"
                                                rel="noopener"
                                                aria-label="{{ $exam->term }}的期末考正參"
                                            >
                                                正參
                                                <x-heroicon-o-arrow-top-right-on-square
                                                    class="size-4"
                                                />
                                            </x-link-button>
                                        @else
                                                —
                                        @endif
                                    </x-table-column>
                                    <x-table-column class="text-center">
                                        @if ($exam->finalReferenceSecondary)
                                            <x-link-button
                                                href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->finalReferenceSecondary }}"
                                                variant="link"
                                                target="_blank"
                                                rel="noopener"
                                                aria-label="{{ $exam->term }}的期末考副參"
                                            >
                                                副參
                                                <x-heroicon-o-arrow-top-right-on-square
                                                    class="size-4"
                                                />
                                            </x-link-button>
                                        @else
                                                —
                                        @endif
                                    </x-table-column>
                                </x-table-row>
                            @endforeach
                        </x-table-body>
                    </x-table>
                </div>
            </x-card>
        @endif

        <x-common-links class="mb-6" />

        <x-card title="免責聲明">
            <p class="text-sm text-warm-600 dark:text-zinc-400">
                課程資料來自國立空中大學之公開資料，基於合理使用原則，以非商用、公開的方式供其他上課同學參考使用，資料版權屬於國立空中大學所有。本站只搜集課程之詮釋資料（Metadata），例如課程名稱、教師、學分數、上課時間等，不保存其他資料。
            </p>
        </x-card>
    </div>
</x-layout>
