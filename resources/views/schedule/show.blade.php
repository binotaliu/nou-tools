<x-layout
    :title="($viewModel->name ?: '我的課表') . ' - NOU 小幫手'"
    :noindex="true"
>
    @php
        $hasCourses = count($viewModel->items) > 0;
    @endphp

    <div class="mx-auto max-w-5xl" x-data>
        <div
            x-show="$store.network.offline"
            x-cloak
            class="mb-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 print:hidden"
            role="status"
            aria-live="polite"
        >
            <x-heroicon-o-signal-slash class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-semibold">目前處於離線狀態</p>
                <p class="mt-1">這是先前載入過的快取內容，可能不是最新資料。可能是 NOU 小幫手網站發生問題，或你的裝置目前連不上網路，因此有部分的功能無法使用。本頁視訊上課連結仍可正常使用。</p>
            </div>
        </div>

        @if ($shouldPromptRememberSchedule)
            <div
                x-data="{ show: !$store.network.offline }"
                x-cloak
                data-testid="remember-schedule-modal-wrapper"
            >
                <x-modal
                    name="show"
                    title="要記住這個課表嗎？"
                    description="看樣子這個課表不是在此瀏覽器上建立的。要將此課表記住在此瀏覽器上嗎？記住後仍可使用其他瀏覽器或裝置開啟課表。"
                    data-testid="remember-schedule-modal"
                >
                    <form
                        method="POST"
                        action="{{ route('schedules.remember', $viewModel->uuid) }}"
                        class="flex justify-end gap-2"
                    >
                        @csrf
                        <x-button
                            type="button"
                            variant="secondary"
                            @click="show = false"
                            data-testid="remember-schedule-dismiss"
                            data-analytics-event="remember_schedule_dismiss"
                            data-analytics-feature="schedule"
                        >
                            不用了
                        </x-button>
                        <x-button
                            type="submit"
                            variant="primary"
                            data-testid="remember-schedule-confirm"
                            data-analytics-event="remember_schedule_confirm"
                            data-analytics-feature="schedule"
                        >
                            記住課表
                        </x-button>
                    </form>
                </x-modal>
            </div>
        @endif

        <div
            class="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row"
        >
            <div>
                <h2
                    class="mb-2 text-3xl font-bold text-warm-900 dark:text-zinc-100"
                >
                    {{ $viewModel->name ?: '我的課表' }}
                </h2>
                <p
                    class="mt-1 flex items-center gap-1 text-sm text-warm-600 dark:text-zinc-400 print:hidden"
                >
                    <x-heroicon-o-information-circle class="inline size-4" />
                    小提示：將此頁加入瀏覽器書籤，下次即可快速開啟課表。
                </p>
            </div>

            <div class="flex w-full flex-col items-end gap-2 lg:w-auto">
                <div
                    class="flex w-full flex-col-reverse gap-2 sm:flex-row lg:w-auto print:hidden"
                >
                    <div class="flex w-full shrink-0 gap-2 sm:w-1/2 lg:w-auto">
                        <x-link-button
                            :href="route('schedules.edit', $viewModel->uuid)"
                            variant="secondary"
                            class="w-full sm:w-1/2 lg:w-auto"
                            data-analytics-event="schedule_edit"
                            data-analytics-feature="schedule"
                        >
                            <x-heroicon-o-pencil-square class="size-4" />
                            編輯
                        </x-link-button>

                        <x-link-button
                            :href="route('schedules.customize', $viewModel->uuid)"
                            variant="secondary"
                            class="w-full sm:w-1/2 lg:w-auto"
                            data-analytics-event="schedule_customize_open"
                            data-analytics-feature="schedule"
                        >
                            <x-heroicon-o-cog-6-tooth class="size-4" />
                            自訂
                        </x-link-button>
                    </div>

                    <x-link-button
                        :href="route('learning-progress.show', [$viewModel->uuid, $viewModel->selectedTerm])"
                        variant="secondary"
                        class="w-full sm:w-1/2 lg:w-auto"
                        data-analytics-event="learning_progress_open"
                        data-analytics-feature="learning_progress"
                    >
                        <x-heroicon-o-clipboard class="size-4" />
                        學習進度表
                    </x-link-button>

                    <x-link-button
                        :href="route('schedules.subscribe', $viewModel->uuid)"
                        variant="primary"
                        class="w-full sm:w-1/2 lg:w-auto"
                        data-analytics-event="calendar_subscribe_open"
                        data-analytics-feature="schedule"
                    >
                        <x-heroicon-o-calendar class="inline size-4" />
                        訂閱行事曆
                    </x-link-button>
                </div>

                <form
                    method="GET"
                    action="{{ route('schedules.show', $viewModel->uuid) }}"
                    class="w-full sm:w-1/2 lg:w-32 print:hidden"
                >
                    <label for="term" class="sr-only">選擇學期</label>
                    <x-select
                        id="term"
                        name="term"
                        @change="$event.target.form.submit()"
                        aria-label="選擇學期"
                        class="bg-white dark:bg-zinc-900"
                        data-offline-disable
                    >
                        @foreach ($viewModel->availableTerms as $term)
                            <option
                                value="{{ $term }}"
                                @selected($term === $viewModel->selectedTerm)
                            >
                                {{ \Illuminate\Support\Str::toShortSemesterDisplay($term) }}
                            </option>
                        @endforeach
                    </x-select>
                </form>

                <span
                    class="hidden text-sm text-warm-600 dark:text-zinc-400 print:inline"
                >
                    {{ \Illuminate\Support\Str::toSemesterDisplay($viewModel->selectedTerm) }}
                </span>
            </div>
        </div>

        @if ($viewModel->displayOptions->showGreeting)
            <x-greeting class="mb-4 print:hidden" />
        @endif

        <x-alt-uu-banner class="print:hidden" />

        @if (! $hasCourses)
            <x-card class="mb-8" title="此學期尚無課程">
                <div class="space-y-3 text-warm-700 dark:text-zinc-300">
                    <p>目前選擇的學期
                    <span class="font-semibold text-warm-900 dark:text-zinc-100"> {{ \Illuminate\Support\Str::toSemesterDisplay($viewModel->selectedTerm) }} </span>
                    沒有課程。</p>

                    <p class="text-sm text-warm-600 dark:text-zinc-400">您可以切換其他學期，或前往
                    <a href="{{ route('schedules.edit', $viewModel->uuid) }}" class="font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"> 編輯課表 </a>
                    新增課程。</p>
                </div>
            </x-card>
        @endif

        {{-- Schedule Items - Responsive Table/Cards --}}
        @if ($viewModel->displayOptions->showScheduleItems && $hasCourses)
            <x-schedule-items
                :items="$viewModel->items"
                :scheduleUuid="$viewModel->uuid"
                :hasAnyOverride="$viewModel->hasAnyOverride"
            />
        @endif

        @php
            $hasTentative = $viewModel->items->toCollection()->contains(fn ($item) => $item->courseClass?->isTentative);
            $pendingItems = $viewModel->items->toCollection()->filter(fn ($item) => $item->courseClassId === null);
            $hasPending = $pendingItems->isNotEmpty();
        @endphp

        @if ($hasTentative)
            <div
                class="mb-8 flex items-start justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 print:hidden"
                role="status"
            >
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle
                        class="mt-0.5 size-5 shrink-0"
                    />
                    <p>尚有未選擇班級的課程，開學分班後記得回來選擇班級，才能看到視訊面授連結喔！</p>
                </div>
            </div>
        @endif

        @if ($hasPending)
            <x-card class="mb-8" title="尚有未選擇班級的課程">
                <ul class="space-y-2">
                    @foreach ($pendingItems as $item)
                        <li
                            class="flex items-center justify-between gap-3 text-warm-700 dark:text-zinc-300"
                        >
                            <span>{{ $item->courseName }}</span>
                            <a
                                href="{{ route('schedules.edit', $viewModel->uuid) }}"
                                class="shrink-0 font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
                            >
                                前往選擇班級
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        @if ($viewModel->displayOptions->showCommonLinks)
            <x-common-links
                class="mb-8 print:hidden"
                :customLinks="$viewModel->customLinks"
            />
        @endif

        {{-- Schedule Calendar View --}}
        @if ($viewModel->displayOptions->showClassDates && count($viewModel->items) > 0)
            <div class="mb-8">
                <h3
                    class="mb-4 text-2xl font-bold text-warm-900 dark:text-zinc-100"
                >
                    面授日期
                </h3>
                @if ($viewModel->hasAnyOverride)
                    <p
                        class="mb-4 flex items-center gap-1 text-sm text-warm-600 dark:text-zinc-400"
                    >
                        <x-heroicon-o-exclamation-triangle
                            class="size-4 text-orange-600"
                        />
                        表示該次面授時間與一般時間不同
                    </p>
                @endif

                <div
                    class="mb-4 grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2 print:grid-cols-1"
                >
                    @foreach ($viewModel->months as $month)
                        <x-card :title="$month->monthDisplay">
                            <div
                                class="grid grid-cols-1 space-y-3 gap-x-6 gap-y-1 print:grid-cols-2"
                            >
                                @foreach ($month->dates as $date)
                                    <div
                                        class="break-inside-avoid-page border-l-4 border-warm-500 py-2 pl-4"
                                    >
                                        <div
                                            class="mb-1 font-semibold text-warm-900 dark:text-zinc-100"
                                        >
                                            {{ $date->formattedDate() }}
                                        </div>
                                        <div class="space-y-1">
                                            @foreach ($date->courses as $course)
                                                <div
                                                    class="text-sm text-warm-700 dark:text-zinc-300"
                                                >
                                                    <span class="font-semibold">
                                                        {{ $course->courseName }}
                                                    </span>
                                                    @if ($course->isTentative)
                                                        <x-class-code>
                                                            尚未分班
                                                        </x-class-code>
                                                    @else
                                                        <x-class-code>
                                                            {{ $course->code }}
                                                        </x-class-code>
                                                    @endif
                                                    <br />
                                                    <span
                                                        class="inline-flex items-center gap-1 text-warm-600 dark:text-zinc-400"
                                                    >
                                                        {{ $course->time }}
                                                        @if ($course->hasOverride)
                                                            <x-heroicon-o-exclamation-triangle
                                                                class="size-4 text-warm-500 dark:text-zinc-400"
                                                                title="該次課程時間與一般時間不同"
                                                            />
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- School Calendar --}}
        @if ($viewModel->displayOptions->showSchoolCalendar)
            <x-school-calendar class="mb-8" :term="$viewModel->selectedTerm" />
        @endif

        @if ($viewModel->displayOptions->showExamInfo)
            <x-card
                class="mb-8"
                title="考試資訊"
                subtitle="以下為您加入課表的科目之期中 / 期末考試日期與節次。"
            >
                {{-- 手機：卡片列表 --}}
                <div class="space-y-3 md:hidden">
                    @forelse ($viewModel->exams as $exam)
                        <div
                            class="rounded-lg border border-warm-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div
                                        class="font-semibold text-warm-900 dark:text-zinc-100"
                                    >
                                        {{ $exam->courseName }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-2">
                                        @if ($exam->classCode)
                                            <x-class-code>
                                                {{ $exam->classCode }}
                                            </x-class-code>
                                        @endif

                                        <a
                                            href="{{ route('course.show', $exam->courseId) }}#previous-exams"
                                            class="mr-3 inline-flex items-center gap-1 text-sm font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100 print:hidden"
                                            aria-label="{{ $exam->courseName }} 的課程資訊"
                                        >
                                            <x-heroicon-o-information-circle
                                                class="inline size-4"
                                                aria-hidden="true"
                                            />
                                            考古題
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                @if (! str_ends_with($viewModel->selectedTerm, 'C'))
                                    <div>
                                        <p
                                            class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase dark:text-zinc-400"
                                        >期中考</p>
                                        @if ($exam->midtermDate)
                                            <div
                                                class="font-semibold text-warm-900 dark:text-zinc-100"
                                            >
                                                {{ $exam->formattedMidtermDate() }}
                                            </div>

                                            @if ($exam->formattedExamTime())
                                                <div
                                                    class="mt-1 text-sm text-warm-600 dark:text-zinc-400"
                                                >
                                                    {{ $exam->formattedExamTime() }}
                                                </div>
                                            @endif
                                        @else
                                            <div
                                                class="text-warm-500 dark:text-zinc-400"
                                            >
                                                —
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div>
                                    <p
                                        class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase dark:text-zinc-400"
                                    >期末考</p>
                                    @if ($exam->finalDate)
                                        <div
                                            class="font-semibold text-warm-900 dark:text-zinc-100"
                                        >
                                            {{ $exam->formattedFinalDate() }}
                                        </div>

                                        @if ($exam->formattedExamTime())
                                            <div
                                                class="mt-1 text-sm text-warm-600 dark:text-zinc-400"
                                            >
                                                {{ $exam->formattedExamTime() }}
                                            </div>
                                        @endif
                                    @else
                                        <div
                                            class="text-warm-500 dark:text-zinc-400"
                                        >
                                            —
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div
                            class="px-4 py-16 text-center text-warm-500 dark:text-zinc-400"
                        >
                            您的課表中沒有任何科目有設定考試日期。
                        </div>
                    @endforelse
                </div>

                {{-- 桌面：維持表格，但只在 md+ 顯示 --}}
                <div class="hidden overflow-x-auto md:block">
                    <x-table class="border-collapse overflow-hidden rounded">
                        <x-table-head>
                            <x-table-row
                                class="rounded-t border-b-2 border-warm-300 bg-warm-100 dark:border-zinc-600 dark:bg-zinc-900"
                            >
                                <x-table-head-column>課程</x-table-head-column>
                                @if (! str_ends_with($viewModel->selectedTerm, 'C'))
                                    <x-table-head-column>
                                        期中考
                                    </x-table-head-column>
                                @endif

                                <x-table-head-column>
                                    期末考
                                </x-table-head-column>
                            </x-table-row>
                        </x-table-head>

                        <x-table-body>
                            @forelse ($viewModel->exams as $exam)
                                <x-table-row
                                    class="border-b border-warm-200 hover:bg-warm-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
                                >
                                    <x-table-column
                                        class="font-semibold text-warm-900 dark:text-zinc-100"
                                    >
                                        {{ $exam->courseName }}
                                        <div
                                            class="mt-1 flex items-center gap-2"
                                        >
                                            @if ($exam->isTentative)
                                                <x-class-code>
                                                    尚未分班
                                                </x-class-code>
                                            @elseif ($exam->classCode)
                                                <x-class-code>
                                                    {{ $exam->classCode }}
                                                </x-class-code>
                                            @endif

                                            <a
                                                href="{{ route('course.show', $exam->courseId) }}#previous-exams"
                                                class="mr-3 inline-flex items-center gap-1 text-sm font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100 print:hidden"
                                                aria-label="{{ $exam->courseName }} 的課程資訊"
                                            >
                                                <x-heroicon-o-information-circle
                                                    class="inline size-4"
                                                    aria-hidden="true"
                                                />
                                                考古題
                                            </a>
                                        </div>
                                    </x-table-column>

                                    @if (! str_ends_with($viewModel->selectedTerm, 'C'))
                                        <x-table-column class="tabular-nums">
                                            @if ($exam->midtermDate)
                                                <div class="font-semibold">
                                                    {{ $exam->formattedMidtermDate() }}
                                                </div>
                                            @else
                                                <div
                                                    class="text-warm-500 dark:text-zinc-400"
                                                >
                                                    —
                                                </div>
                                            @endif

                                            @if ($exam->formattedExamTime())
                                                <div
                                                    class="mt-1 text-sm text-warm-600 dark:text-zinc-400"
                                                >
                                                    {{ $exam->formattedExamTime() }}
                                                </div>
                                            @endif
                                        </x-table-column>
                                    @endif

                                    <x-table-column class="tabular-nums">
                                        @if ($exam->finalDate)
                                            <div class="font-semibold">
                                                {{ $exam->formattedFinalDate() }}
                                            </div>
                                        @else
                                            <div
                                                class="text-warm-500 dark:text-zinc-400"
                                            >
                                                —
                                            </div>
                                        @endif

                                        @if ($exam->formattedExamTime())
                                            <div
                                                class="mt-1 text-sm text-warm-600 dark:text-zinc-400"
                                            >
                                                {{ $exam->formattedExamTime() }}
                                            </div>
                                        @endif
                                    </x-table-column>
                                </x-table-row>
                            @empty
                                <x-table-row>
                                    <x-table-column
                                        colspan="3"
                                        class="px-4 py-16 text-center text-warm-500 dark:text-zinc-400"
                                    >
                                        您的課表中沒有任何科目有設定考試日期。
                                    </x-table-column>
                                </x-table-row>
                            @endforelse
                        </x-table-body>
                    </x-table>
                </div>
            </x-card>
        @endif

        {{-- Announcements --}}
        @if ($viewModel->displayOptions->showAnnouncements)
            <div x-data x-show="!$store.network.offline" x-cloak>
                <x-announcements-widget
                    :schedule="$schedule"
                    class="mb-8 print:hidden"
                />
            </div>
        @endif

        {{-- Share Section --}}
        @if ($viewModel->displayOptions->showShareSection)
            <x-card>
                <div class="flex items-center justify-between gap-4 print:flex">
                    <div class="w-full md:w-auto md:flex-1 print:flex-1">
                        <p class="mb-3 text-warm-700 dark:text-zinc-300">
                            您可以使用以下連結來編輯或檢視此課表，請妥善保管此連結。
                            <br />
                            <span
                                class="inline-flex items-center gap-1 font-semibold text-red-600"
                            >
                                <x-heroicon-o-exclamation-triangle
                                    class="size-4"
                                />
                                注意：任何擁有此連結的人都可以編輯您的課表。
                            </span>
                        </p>

                        <div
                            x-data="nouCopyLink({
                                shareUrl: {{ Js::from(url(route('schedules.show', $viewModel->uuid))) }},
                            })"
                            class="rounded border border-warm-300 bg-white text-sm text-warm-600 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-400"
                        >
                            <div class="flex items-stretch gap-3">
                                <input
                                    class="flex-1 px-3 py-2 font-mono break-all text-warm-600 dark:text-zinc-400 print:hidden"
                                    :value="shareUrl"
                                    readonly
                                    @click="$event.target.select()"
                                    x-ref="shareInput"
                                    aria-label="我的課表連結"
                                />
                                <div
                                    class="hidden items-center px-3 py-2 font-mono break-all text-warm-600 dark:text-zinc-400 print:flex"
                                    x-text="shareUrl"
                                ></div>

                                <div class="shrink-0">
                                    <x-button
                                        type="button"
                                        variant="warm-subtle"
                                        size="sm"
                                        @click="copy()"
                                        x-bind:aria-pressed="copied.toString()"
                                        class="ml-2 h-full rounded-l-none rounded-r whitespace-nowrap print:hidden"
                                    >
                                        <span x-show="!copied">
                                            <x-heroicon-o-clipboard-document
                                                class="inline size-4"
                                            />
                                            複製連結
                                        </span>
                                        <span x-show="copied">
                                            <x-heroicon-o-check
                                                class="inline size-4"
                                            />
                                            已複製！
                                        </span>
                                    </x-button>

                                    <div
                                        class="sr-only"
                                        role="status"
                                        aria-live="polite"
                                        x-text="copied ? '已複製' : ''"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="hidden w-28 flex-col items-center justify-center md:flex print:flex"
                    >
                        <div
                            class="rounded border border-warm-200 bg-white p-2"
                        >
                            {!! DNS2D::getBarcodeSVG(url(route('schedules.show', $viewModel->uuid)), 'QRCODE') !!}
                        </div>
                    </div>
                </div>
            </x-card>
        @endif

        @if ($viewModel->displayOptions->showPrintButton)
            <div class="mt-6 flex justify-end print:hidden">
                <x-button
                    type="button"
                    variant="warm-subtle"
                    @click="$print()"
                    data-testid="schedule-print-button"
                >
                    <x-heroicon-o-printer class="inline size-4" />
                    列印
                </x-button>
            </div>
        @endif
    </div>
</x-layout>
