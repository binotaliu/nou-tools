<x-layout
    :title="($viewModel->name ?: '我的課表') . ' - NOU 小幫手'"
    :noindex="true"
>
    @php
        $hasCourses = count($viewModel->items) > 0;
    @endphp

    <div class="mx-auto max-w-5xl">
        <div
            class="mb-8 flex flex-col items-start justify-between gap-y-4 lg:flex-row"
        >
            <div>
                <h2 class="mb-2 text-3xl font-bold text-warm-900">
                    {{ $viewModel->name ?: '我的課表' }}
                </h2>
                <p
                    class="mt-1 flex items-center gap-1 text-sm text-warm-600 print:hidden"
                >
                    <x-heroicon-o-information-circle class="inline size-4" />
                    小提示：將此頁加入瀏覽器書籤，下次即可快速開啟課表。
                </p>
            </div>

            <div class="flex w-full flex-col items-end gap-2 lg:w-auto">
                <div
                    class="flex w-full flex-col-reverse gap-2 sm:flex-row lg:w-auto print:hidden"
                    x-data="{
                        subscribeOpen: false,
                        includeSchoolCalendar: {{ Js::from($viewModel->calendarSettings['include_school_calendar']) }},
                        includeExams: {{ Js::from($viewModel->calendarSettings['include_exams']) }},
                        classRemindersEnabled: {{ Js::from($viewModel->calendarSettings['class_reminders_enabled']) }},
                        primaryReminder: {{ Js::from($viewModel->calendarSettings['reminder_offsets'][0] ?? 30) }},
                        secondaryReminderEnabled: {{ Js::from(isset($viewModel->calendarSettings['reminder_offsets'][1])) }},
                        secondaryReminder: {{ Js::from($viewModel->calendarSettings['reminder_offsets'][1] ?? 10) }},
                        saveSettingsMessage: '',
                        saveSettingsState: 'idle',
                        isSavingSettings: false,
                        buildReminderOffsets() {
                            const offsets = [Number(this.primaryReminder)]

                            if (this.secondaryReminderEnabled) {
                                offsets.push(Number(this.secondaryReminder))
                            }

                            return [...new Set(offsets)].slice(0, 2)
                        },
                        async saveCalendarSettings() {
                            if (this.isSavingSettings) {
                                return
                            }

                            this.isSavingSettings = true
                            this.saveSettingsMessage = ''
                            this.saveSettingsState = 'idle'

                            try {
                                const csrfToken = document
                                    .querySelector('meta[name=\'csrf-token\']')
                                    ?.getAttribute('content')

                                const response = await fetch({{ Js::from(route('schedules.calendar-settings.update', $viewModel->uuid)) }}, {
                                    method: 'PUT',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken ?? '',
                                    },
                                    body: JSON.stringify({
                                        include_school_calendar: this.includeSchoolCalendar,
                                        include_exams: this.includeExams,
                                        class_reminders_enabled: this.classRemindersEnabled,
                                        reminder_offsets: this.classRemindersEnabled ? this.buildReminderOffsets() : [Number(this.primaryReminder)],
                                    }),
                                })

                                if (!response.ok) {
                                    throw new Error('儲存失敗')
                                }

                                const result = await response.json()
                                const settings = result.calendar_settings ?? null

                                if (settings) {
                                    this.includeSchoolCalendar = Boolean(settings.include_school_calendar)
                                    this.includeExams = Boolean(settings.include_exams)
                                    this.classRemindersEnabled = Boolean(settings.class_reminders_enabled)

                                    const reminderOffsets = Array.isArray(settings.reminder_offsets) && settings.reminder_offsets.length > 0
                                        ? settings.reminder_offsets
                                        : [30]

                                    this.primaryReminder = Number(reminderOffsets[0])
                                    this.secondaryReminderEnabled = reminderOffsets.length > 1
                                    this.secondaryReminder = Number(reminderOffsets[1] ?? this.secondaryReminder)
                                }

                                this.saveSettingsState = 'success'
                                this.saveSettingsMessage = '已儲存設定。'
                            } catch (e) {
                                this.saveSettingsState = 'error'
                                this.saveSettingsMessage = '儲存失敗，請稍後再試。'
                            } finally {
                                this.isSavingSettings = false
                            }
                        },
                    }"
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

                    @php
                        $icsUrl = $viewModel->calendarUrls->ics;
                        $webcalUrl = $viewModel->calendarUrls->webcal;
                        $googleUrl = $viewModel->calendarUrls->google;
                        $outlookWebUrl = $viewModel->calendarUrls->outlook;
                    @endphp

                    <x-button
                        type="button"
                        variant="primary"
                        @click="subscribeOpen = true"
                        class="w-full sm:w-1/2 lg:w-auto"
                        data-analytics-event="calendar_subscribe_open"
                        data-analytics-feature="schedule"
                    >
                        <x-heroicon-o-calendar class="inline size-4" />
                        訂閱行事曆
                    </x-button>

                    {{-- Subscribe modal --}}
                    <x-modal
                        name="subscribeOpen"
                        title="訂閱行事曆"
                        description="先設定此課表的預設訂閱內容，再選擇要使用的訂閱方式。"
                        max-width="max-w-lg"
                    >
                        <div class="grid gap-3">
                            <x-link-button
                                :href="$webcalUrl"
                                variant="ghost"
                                full-width
                                @click="subscribeOpen = false"
                                data-analytics-event="calendar_subscribe"
                                data-analytics-feature="schedule"
                                data-analytics-label="webcal"
                            >
                                Apple 日曆 (iOS / macOS)
                            </x-link-button>

                            <x-link-button
                                :href="$googleUrl"
                                variant="ghost"
                                full-width
                                target="_blank"
                                rel="noopener"
                                @click="subscribeOpen = false"
                                data-analytics-event="calendar_subscribe"
                                data-analytics-feature="schedule"
                                data-analytics-label="google"
                            >
                                Google 日曆
                            </x-link-button>

                            <x-link-button
                                :href="$outlookWebUrl"
                                variant="ghost"
                                full-width
                                target="_blank"
                                rel="noopener"
                                @click="subscribeOpen = false"
                                data-analytics-event="calendar_subscribe"
                                data-analytics-feature="schedule"
                                data-analytics-label="outlook"
                            >
                                Windows 日曆 (Microsoft 365 / Outlook.com)
                            </x-link-button>

                            <x-link-button
                                :href="$webcalUrl"
                                variant="ghost"
                                full-width
                                @click="subscribeOpen = false"
                                data-analytics-event="calendar_subscribe"
                                data-analytics-feature="schedule"
                                data-analytics-label="webcal_generic"
                            >
                                Webcal 連結 (其他支援 Webcal 的行事曆)
                            </x-link-button>

                            <x-link-button
                                :href="$icsUrl"
                                variant="ghost"
                                full-width
                                target="_blank"
                                rel="noopener"
                                :download="true"
                                @click="subscribeOpen = false"
                                data-analytics-event="calendar_download"
                                data-analytics-feature="schedule"
                                data-analytics-label="ics"
                            >
                                下載 iCal（.ics）
                            </x-link-button>
                        </div>

                        <div
                            class="mt-5 space-y-4 border-t border-warm-200 pt-4"
                        >
                            <p class="text-sm text-warm-700">
                                保存此處設定後，原先訂閱的行事曆會在同步時自動更新。若您使用的是
                                Google 日曆，請注意 Google
                                日曆可能需要數小時才會更新訂閱內容。
                            </p>

                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-warm-200 bg-white px-3 py-2"
                            >
                                <input
                                    type="checkbox"
                                    x-model="includeSchoolCalendar"
                                    class="size-4 rounded border-warm-400 text-warm-700 focus:ring-warm-500"
                                />
                                <span class="text-sm font-medium text-warm-800">
                                    包含學校行事曆
                                </span>
                            </label>

                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-warm-200 bg-white px-3 py-2"
                            >
                                <input
                                    type="checkbox"
                                    x-model="includeExams"
                                    class="size-4 rounded border-warm-400 text-warm-700 focus:ring-warm-500"
                                />
                                <span class="text-sm font-medium text-warm-800">
                                    包含考試時段
                                </span>
                            </label>

                            <div
                                class="rounded-lg border border-warm-200 bg-white p-3"
                            >
                                <label
                                    class="flex cursor-pointer items-center gap-3"
                                >
                                    <input
                                        type="checkbox"
                                        x-model="classRemindersEnabled"
                                        class="size-4 rounded border-warm-400 text-warm-700 focus:ring-warm-500"
                                    />
                                    <span
                                        class="text-sm font-medium text-warm-800"
                                    >
                                        面授課程提醒
                                    </span>
                                </label>

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label
                                            class="mb-1 block text-xs font-semibold text-warm-700"
                                        >
                                            第一次提醒
                                        </label>

                                        <x-select
                                            x-model="primaryReminder"
                                            x-bind:disabled="!classRemindersEnabled"
                                            class="bg-white"
                                        >
                                            <option value="5">
                                                課前 5 分鐘
                                            </option>
                                            <option value="10">
                                                課前 10 分鐘
                                            </option>
                                            <option value="15">
                                                課前 15 分鐘
                                            </option>
                                            <option value="30">
                                                課前 30 分鐘
                                            </option>
                                            <option value="60">
                                                課前 1 小時
                                            </option>
                                            <option value="120">
                                                課前 2 小時
                                            </option>
                                            <option value="180">
                                                課前 3 小時
                                            </option>
                                            <option value="1440">
                                                課前 1 天
                                            </option>
                                        </x-select>
                                    </div>

                                    <div>
                                        <label
                                            class="mb-1 block text-xs font-semibold text-warm-700"
                                        >
                                            第二次提醒（可選）
                                        </label>

                                        <div class="flex gap-2">
                                            <label
                                                class="inline-flex items-center gap-2 rounded-lg border border-warm-200 px-2 py-1 text-xs text-warm-700"
                                            >
                                                <input
                                                    type="checkbox"
                                                    x-model="secondaryReminderEnabled"
                                                    x-bind:disabled="!classRemindersEnabled"
                                                    class="size-3 rounded border-warm-400 text-warm-700 focus:ring-warm-500"
                                                />
                                                啟用
                                            </label>

                                            <x-select
                                                x-model="secondaryReminder"
                                                x-bind:disabled="!classRemindersEnabled || !secondaryReminderEnabled"
                                                class="bg-white"
                                            >
                                                <option value="5">
                                                    課前 5 分鐘
                                                </option>
                                                <option value="10">
                                                    課前 10 分鐘
                                                </option>
                                                <option value="15">
                                                    課前 15 分鐘
                                                </option>
                                                <option value="30">
                                                    課前 30 分鐘
                                                </option>
                                                <option value="60">
                                                    課前 1 小時
                                                </option>
                                                <option value="120">
                                                    課前 2 小時
                                                </option>
                                                <option value="180">
                                                    課前 3 小時
                                                </option>
                                                <option value="1440">
                                                    課前 1 天
                                                </option>
                                            </x-select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p
                                x-show="saveSettingsMessage"
                                x-text="saveSettingsMessage"
                                x-bind:class="
                                    saveSettingsState === 'success'
                                        ? 'text-sm text-green-700'
                                        : 'text-sm text-red-700'
                                "
                            ></p>
                        </div>

                        <x-slot:footer>
                            <div class="flex w-full justify-end gap-2">
                                <x-button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    @click="saveCalendarSettings()"
                                    x-bind:disabled="isSavingSettings"
                                    class="px-4 py-2 text-sm"
                                >
                                    <span x-show="!isSavingSettings">
                                        儲存設定
                                    </span>
                                    <span x-show="isSavingSettings">
                                        儲存中...
                                    </span>
                                </x-button>

                                <x-button
                                    type="button"
                                    variant="warm-subtle"
                                    size="sm"
                                    @click="subscribeOpen = false"
                                    class="px-4 py-2 text-sm"
                                >
                                    關閉
                                </x-button>
                            </div>
                        </x-slot>
                    </x-modal>
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
                        onchange="this.form.submit()"
                        aria-label="選擇學期"
                        class="bg-white"
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

                <span class="hidden text-sm text-warm-600 print:inline">
                    {{ \Illuminate\Support\Str::toSemesterDisplay($viewModel->selectedTerm) }}
                </span>
            </div>
        </div>

        @if ($viewModel->displayOptions['show_greeting'])
            <x-greeting class="mb-4 print:hidden" />
        @endif

        <x-alt-uu-banner class="print:hidden" />

        @if (! $hasCourses)
            <x-card class="mb-8" title="此學期尚無課程">
                <div class="space-y-3 text-warm-700">
                    <p>
                        目前選擇的學期
                        <span class="font-semibold text-warm-900">
                            {{ \Illuminate\Support\Str::toSemesterDisplay($viewModel->selectedTerm) }}
                        </span>
                        沒有課程。
                    </p>

                    <p class="text-sm text-warm-600">
                        您可以切換其他學期，或前往
                        <a
                            href="{{ route('schedules.edit', $viewModel->uuid) }}"
                            class="font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline"
                        >
                            編輯課表
                        </a>
                        新增課程。
                    </p>
                </div>
            </x-card>
        @endif

        {{-- Schedule Items - Responsive Table/Cards --}}
        @if ($viewModel->displayOptions['show_schedule_items'] && $hasCourses)
            <x-schedule-items
                :items="$viewModel->items"
                :scheduleUuid="$viewModel->uuid"
                :hasAnyOverride="$viewModel->hasAnyOverride"
            />
        @endif

        @if ($viewModel->displayOptions['show_common_links'])
            <x-common-links
                class="mb-8 print:hidden"
                :customLinks="$viewModel->customLinks"
            />
        @endif

        {{-- Schedule Calendar View --}}
        @if ($viewModel->displayOptions['show_class_dates'] && count($viewModel->items) > 0)
            <div class="mb-8">
                <h3 class="mb-4 text-2xl font-bold text-warm-900">面授日期</h3>
                @if ($viewModel->hasAnyOverride)
                    <p
                        class="mb-4 flex items-center gap-1 text-sm text-warm-600"
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
                                            class="mb-1 font-semibold text-warm-900"
                                        >
                                            {{ $date->formattedDate() }}
                                        </div>
                                        <div class="space-y-1">
                                            @foreach ($date->courses as $course)
                                                <div
                                                    class="text-sm text-warm-700"
                                                >
                                                    <span class="font-semibold">
                                                        {{ $course->courseName }}
                                                    </span>
                                                    <x-class-code>
                                                        {{ $course->code }}
                                                    </x-class-code>
                                                    <br />
                                                    <span
                                                        class="inline-flex items-center gap-1 text-warm-600"
                                                    >
                                                        {{ $course->time }}
                                                        @if ($course->hasOverride)
                                                            <x-heroicon-o-exclamation-triangle
                                                                class="size-4 text-warm-500"
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
        @if ($viewModel->displayOptions['show_school_calendar'])
            <x-school-calendar class="mb-8" />
        @endif

        @if ($viewModel->displayOptions['show_exam_info'])
            <x-card
                class="mb-8"
                title="考試資訊"
                subtitle="以下為您加入課表的科目之期中 / 期末考試日期與節次。"
            >
                {{-- 手機：卡片列表 --}}
                <div class="space-y-3 md:hidden">
                    @forelse ($viewModel->exams as $exam)
                        <div
                            class="rounded-lg border border-warm-200 bg-white p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="font-semibold text-warm-900">
                                        {{ $exam->courseName }}
                                    </div>
                                    @if ($exam->classCode)
                                        <div
                                            class="mt-1 flex items-center gap-2"
                                        >
                                            <x-class-code>
                                                {{ $exam->classCode }}
                                            </x-class-code>

                                            <a
                                                href="{{ route('course.show', $exam->courseId) }}#previous-exams"
                                                class="mr-3 inline-flex items-center gap-1 text-sm font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline print:hidden"
                                                aria-label="{{ $exam->courseName }} 的課程資訊"
                                            >
                                                <x-heroicon-o-information-circle
                                                    class="inline size-4"
                                                    aria-hidden="true"
                                                />
                                                考古題
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                @if (! str_ends_with($viewModel->selectedTerm, 'C'))
                                    <div>
                                        <p
                                            class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase"
                                        >
                                            期中考
                                        </p>
                                        @if ($exam->midtermDate)
                                            <div
                                                class="font-semibold text-warm-900"
                                            >
                                                {{ $exam->formattedMidtermDate() }}
                                            </div>

                                            @if ($exam->formattedExamTime())
                                                <div
                                                    class="mt-1 text-sm text-warm-600"
                                                >
                                                    {{ $exam->formattedExamTime() }}
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-warm-500">—</div>
                                        @endif
                                    </div>
                                @endif

                                <div>
                                    <p
                                        class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase"
                                    >
                                        期末考
                                    </p>
                                    @if ($exam->finalDate)
                                        <div
                                            class="font-semibold text-warm-900"
                                        >
                                            {{ $exam->formattedFinalDate() }}
                                        </div>

                                        @if ($exam->formattedExamTime())
                                            <div
                                                class="mt-1 text-sm text-warm-600"
                                            >
                                                {{ $exam->formattedExamTime() }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-warm-500">—</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-16 text-center text-warm-500">
                            您的課表中沒有任何科目有設定考試日期。
                        </div>
                    @endforelse
                </div>

                {{-- 桌面：維持表格，但只在 md+ 顯示 --}}
                <div class="hidden overflow-x-auto md:block">
                    <x-table class="border-collapse overflow-hidden rounded">
                        <x-table-head>
                            <x-table-row
                                class="rounded-t border-b-2 border-warm-300 bg-warm-100"
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
                                    class="border-b border-warm-200 hover:bg-warm-50"
                                >
                                    <x-table-column
                                        class="font-semibold text-warm-900"
                                    >
                                        {{ $exam->courseName }}
                                        @if ($exam->classCode)
                                            <div
                                                class="mt-1 flex items-center gap-2"
                                            >
                                                <x-class-code>
                                                    {{ $exam->classCode }}
                                                </x-class-code>

                                                <a
                                                    href="{{ route('course.show', $exam->courseId) }}#previous-exams"
                                                    class="mr-3 inline-flex items-center gap-1 text-sm font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline print:hidden"
                                                    aria-label="{{ $exam->courseName }} 的課程資訊"
                                                >
                                                    <x-heroicon-o-information-circle
                                                        class="inline size-4"
                                                        aria-hidden="true"
                                                    />
                                                    考古題
                                                </a>
                                            </div>
                                        @endif
                                    </x-table-column>

                                    @if (! str_ends_with($viewModel->selectedTerm, 'C'))
                                        <x-table-column class="tabular-nums">
                                            @if ($exam->midtermDate)
                                                <div class="font-semibold">
                                                    {{ $exam->formattedMidtermDate() }}
                                                </div>
                                            @else
                                                <div class="text-warm-500">
                                                    —
                                                </div>
                                            @endif

                                            @if ($exam->formattedExamTime())
                                                <div
                                                    class="mt-1 text-sm text-warm-600"
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
                                            <div class="text-warm-500">—</div>
                                        @endif

                                        @if ($exam->formattedExamTime())
                                            <div
                                                class="mt-1 text-sm text-warm-600"
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
                                        class="px-4 py-16 text-center text-warm-500"
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

        {{-- Share Section --}}
        @if ($viewModel->displayOptions['show_share_section'])
            <x-card>
                <div class="flex items-center justify-between gap-4 print:flex">
                    <div class="w-full md:w-auto md:flex-1 print:flex-1">
                        <p class="mb-3 text-warm-700">
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
                            x-data="{
                                shareUrl: {{ Js::from(url(route('schedules.show', $viewModel->uuid))) }},
                                copied: false,
                                async copy() {
                                    try {
                                        await navigator.clipboard.writeText(this.shareUrl)
                                    } catch (e) {
                                        this.$refs.shareInput.select()
                                        document.execCommand('copy')
                                    }

                                    this.copied = true
                                    setTimeout(() => (this.copied = false), 2000)
                                },
                            }"
                            class="rounded border border-warm-300 bg-white text-sm text-warm-600"
                        >
                            <div class="flex items-stretch gap-3">
                                <input
                                    class="flex-1 px-3 py-2 font-mono break-all text-warm-600 print:hidden"
                                    :value="shareUrl"
                                    readonly
                                    @click="$event.target.select()"
                                    x-ref="shareInput"
                                    aria-label="我的課表連結"
                                />
                                <div
                                    class="hidden items-center px-3 py-2 font-mono break-all text-warm-600 print:flex"
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

        @if ($viewModel->displayOptions['show_print_button'])
            <div class="mt-6 flex justify-end print:hidden">
                <x-button
                    type="button"
                    variant="warm-subtle"
                    onclick="window.print()"
                >
                    <x-heroicon-o-printer class="inline size-4" />
                    列印
                </x-button>
            </div>
        @endif
    </div>
</x-layout>
