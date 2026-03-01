<x-layout :title="$course->name . ' - 檢視課程 - NOU 小幫手'">
    <div class="mx-auto max-w-5xl">
        <div class="mb-8">
            <x-link-button
                :href="isset($previousSchedule) ? route('schedules.show', $previousSchedule->token) : url()->previous()"
                variant="text-link"
                class="mb-4"
            >
                <x-heroicon-o-chevron-left class="size-4" />
                回到我的課表
            </x-link-button>
            <h2 class="mb-2 text-3xl font-bold text-warm-900">
                {{ $course->name }}
            </h2>

            @if (! empty($course->term))
                <div class="mb-4 text-sm text-warm-600">
                    {{ \Illuminate\Support\Str::toSemesterDisplay($course->term) }}
                </div>
            @endif
        </div>

        {{-- Course Information --}}
        <x-card class="mb-6" title="課程資訊">
            <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- 科目內容 --}}
                @if ($course->description_url)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">
                            科目內容
                        </dt>
                        <dd class="text-warm-700">
                            <x-link-button
                                :href="$course->description_url"
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
                @if ($course->credit_type)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">
                            必/選修
                        </dt>
                        <dd class="text-warm-700">
                            {{ $course->credit_type }}
                        </dd>
                    </div>
                @endif

                {{-- 學分 --}}
                @if ($course->credits)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">學分</dt>
                        <dd class="text-warm-700">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center gap-1 text-orange-500"
                                    aria-hidden="true"
                                >
                                    @php
                                        $starCount = (int) floor($course->credits);
                                        $displayStars = min($starCount, 6);
                                    @endphp

                                    @for ($i = 0; $i < $displayStars; $i++)
                                        <x-heroicon-s-star class="size-4" />
                                    @endfor

                                    @if ($starCount > $displayStars)
                                        <span class="text-xs text-warm-600">
                                            +{{ $starCount - $displayStars }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-sm text-warm-600">
                                    {{ $course->credits }} 學分
                                </div>
                            </div>
                        </dd>
                    </div>
                @endif

                {{-- 學系 --}}
                @if ($course->department)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">學系</dt>
                        <dd class="text-warm-700">
                            {{ $course->department }}
                        </dd>
                    </div>
                @endif

                {{-- 面授類別 --}}
                @if ($course->in_person_class_type)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">
                            面授類別
                        </dt>
                        <dd class="text-warm-700">
                            {{ $course->in_person_class_type }}
                        </dd>
                    </div>
                @endif

                {{-- 媒體 --}}
                @if ($course->media)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">媒體</dt>
                        <dd class="text-warm-700">{{ $course->media }}</dd>
                    </div>
                @endif

                {{-- 多媒體簡介 --}}
                @if ($course->multimedia_url)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">
                            多媒體簡介
                        </dt>
                        <dd class="text-warm-700">
                            <x-link-button
                                :href="$course->multimedia_url"
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
                @if ($course->nature)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">
                            課程性質
                        </dt>
                        <dd class="text-warm-700">{{ $course->nature }}</dd>
                    </div>
                @endif

                @if ($course->midterm_date || $course->final_date || $course->exam_time_start || $course->exam_time_end)
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">
                            考試資訊
                        </dt>
                        <dd class="text-warm-700">
                            @if ($course->midterm_date)
                                <div class="mb-2">
                                    <div class="font-semibold">期中考</div>
                                    <div
                                        class="flex items-center justify-start gap-x-2 text-sm text-warm-700 tabular-nums"
                                    >
                                        <div>
                                            {{ Date::parse($course->midterm_date)->isoFormat('M/D (dd)') }}
                                        </div>

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div
                                                class="text-sm whitespace-nowrap text-warm-600"
                                            >
                                                @if ($course->exam_time_start && $course->exam_time_end)
                                                    {{ $course->exam_time_start }}
                                                    -
                                                    {{ $course->exam_time_end }}
                                                @else
                                                    {{ $course->exam_time_start ?? $course->exam_time_end }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($course->final_date)
                                <div>
                                    <div class="font-semibold">期末考</div>
                                    <div
                                        class="flex items-center justify-start gap-x-2 text-sm text-warm-700 tabular-nums"
                                    >
                                        <div>
                                            {{ Date::parse($course->final_date)->isoFormat('M/D (dd)') }}
                                        </div>

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div
                                                class="text-sm whitespace-nowrap text-warm-600"
                                            >
                                                @if ($course->exam_time_start && $course->exam_time_end)
                                                    {{ $course->exam_time_start }}
                                                    -
                                                    {{ $course->exam_time_end }}
                                                @else
                                                    {{ $course->exam_time_start ?? $course->exam_time_end }}
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
        @if ($course->textbook !== null)
            <x-card class="mb-6" title="教科書資訊">
                <dl class="grid grid-cols-1 gap-6 text-warm-700 md:grid-cols-2">
                    <div>
                        <dt class="mb-2 font-semibold text-warm-900">書名</dt>
                        <dd class="text-warm-700">
                            {{ $course->textbook->book_title }}
                        </dd>
                    </div>

                    @if ($course->textbook->edition)
                        <div>
                            <dt class="mb-2 font-semibold text-warm-900">
                                版本
                            </dt>
                            <dd class="text-warm-700">
                                {{ $course->textbook->edition }}
                            </dd>
                        </div>
                    @endif

                    @if ($course->textbook->price_info && is_numeric($course->textbook->price_info))
                        <div>
                            <dt class="mb-2 font-semibold text-warm-900">
                                價格
                            </dt>
                            <dd class="text-warm-700">
                                ${{ number_format($course->textbook->price_info) }}
                            </dd>
                        </div>
                    @else($course->textbook->price_info)
                        <div>
                            <dt class="mb-2 font-semibold text-warm-900">
                                坊間教科書資訊
                            </dt>
                            <dd class="text-warm-700">
                                {{ $course->textbook->price_info }}
                            </dd>
                        </div>
                    @endif

                    @if ($course->textbook->reference_url)
                        <div>
                            <dt class="mb-2 font-semibold text-warm-900">
                                參考連結
                            </dt>
                            <dd class="text-warm-700">
                                <x-link-button
                                    :href="$course->textbook->reference_url"
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

        @if ($course->classes->isNotEmpty())
            <x-card class="mb-6" title="視訊面授班級與上課時間">
                @php
                    $typeOrder = ['morning', 'afternoon', 'evening', 'full_remote', 'micro_credit', 'computer_lab'];
                    $grouped = $course->classes->groupBy(fn ($c) => $c->type->value);
                @endphp

                <div class="space-y-6">
                    @foreach ($typeOrder as $type)
                        @if (isset($grouped[$type]) && $grouped[$type]->isNotEmpty())
                            <div>
                                <div class="mb-3 font-semibold text-warm-900">
                                    {{ \App\Enums\CourseClassType::tryFrom($type)?->label() ?? $type }}
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                                >
                                    @foreach ($grouped[$type] as $class)
                                        <div
                                            class="rounded-lg border-2 border-warm-200 bg-warm-50 p-4"
                                        >
                                            <div class="mb-3">
                                                <div
                                                    class="flex items-start justify-between"
                                                >
                                                    <div>
                                                        <div
                                                            class="font-semibold text-warm-900"
                                                        >
                                                            {{ $class->code }}
                                                        </div>
                                                        @if ($class->teacher_name)
                                                            <div
                                                                class="mt-1 truncate text-sm text-warm-700"
                                                            >
                                                                @php
                                                                    $teacher = $class->teacher_name;
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
                                                        class="text-sm whitespace-nowrap text-warm-600"
                                                    >
                                                        @if ($class->start_time)
                                                            <div>
                                                                {{ $class->start_time }}
                                                                -
                                                                {{ $class->end_time }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($class->schedules->isNotEmpty())
                                                <div
                                                    class="mt-2 rounded bg-white p-3"
                                                >
                                                    <p
                                                        class="mb-2 text-sm font-semibold text-warm-900"
                                                    >
                                                        視訊面授日期：
                                                    </p>

                                                    {{-- 列出每一天；只有 schedule 本身有 start_time/end_time (override) 時，才在該日期旁顯示覆寫時間 --}}
                                                    <div
                                                        class="space-y-1 text-sm text-warm-700"
                                                    >
                                                        @php
                                                            $schedulesByDate = $class->schedules->sortBy('date')->groupBy(function ($s) {
                                                                return $s->date->format('Y-m-d');
                                                            });
                                                        @endphp

                                                        @foreach ($schedulesByDate as $dateKey => $schedules)
                                                            @php
                                                                $s = $schedules->first();
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

                                                                @if ($s->start_time || $s->end_time)
                                                                    <div
                                                                        class="text-sm whitespace-nowrap text-warm-600"
                                                                    >
                                                                        @if ($s->start_time && $s->end_time)
                                                                            {{ $s->start_time }}
                                                                            -
                                                                            {{ $s->end_time }}
                                                                        @elseif ($s->start_time)
                                                                            {{ $s->start_time }}
                                                                        @else
                                                                            {{ $s->end_time }}
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <p
                                                    class="mt-2 text-sm text-warm-600"
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
        @if ($previousExams->isNotEmpty())
            <x-card class="mb-6" title="考古題" id="previous-exams">
                <div class="overflow-x-auto">
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
                            @foreach ($previousExams as $exam)
                                <x-table-row>
                                    <x-table-head-column
                                        scope="row"
                                        class="text-center tabular-nums"
                                    >
                                        {{ $exam->term ?? '-' }}
                                    </x-table-head-column>
                                    <x-table-column class="text-center">
                                        @if ($exam->midterm_reference_primary)
                                            <x-link-button
                                                href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->midterm_reference_primary }}"
                                                variant="link"
                                                target="_blank"
                                                rel="noopener"
                                                aria-label="{{ $exam->term }}的期中考正參"
                                            >
                                                正參考
                                                <x-heroicon-o-arrow-top-right-on-square
                                                    class="size-4"
                                                />
                                            </x-link-button>
                                        @else
                                                —
                                        @endif
                                    </x-table-column>
                                    <x-table-column class="text-center">
                                        @if ($exam->midterm_reference_secondary)
                                            <x-link-button
                                                href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->midterm_reference_secondary }}"
                                                variant="link"
                                                target="_blank"
                                                rel="noopener"
                                                aria-label="{{ $exam->term }}的期中考副參"
                                            >
                                                副參考
                                                <x-heroicon-o-arrow-top-right-on-square
                                                    class="size-4"
                                                />
                                            </x-link-button>
                                        @else
                                                —
                                        @endif
                                    </x-table-column>
                                    <x-table-column class="text-center">
                                        @if ($exam->final_reference_primary)
                                            <x-link-button
                                                href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->final_reference_primary }}"
                                                variant="link"
                                                target="_blank"
                                                rel="noopener"
                                                aria-label="{{ $exam->term }}的期末考正參"
                                            >
                                                正參考
                                                <x-heroicon-o-arrow-top-right-on-square
                                                    class="size-4"
                                                />
                                            </x-link-button>
                                        @else
                                                —
                                        @endif
                                    </x-table-column>
                                    <x-table-column class="text-center">
                                        @if ($exam->final_reference_secondary)
                                            <x-link-button
                                                href="https://noustud.nou.edu.tw/shared_tmp/work/exa/refans/{{ $exam->final_reference_secondary }}"
                                                variant="link"
                                                target="_blank"
                                                rel="noopener"
                                                aria-label="{{ $exam->term }}的期末考副參"
                                            >
                                                副參考
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
            <p class="text-sm text-warm-600">
                課程資料來自國立空中大學之公開資料，基於合理使用原則，以非商用、公開的方式供其他上課同學參考使用，資料版權屬於國立空中大學所有。本站只搜集課程之詮釋資料（Metadata），例如課程名稱、教師、學分數、上課時間等，不保存其他資料。
            </p>
        </x-card>
    </div>
</x-layout>
