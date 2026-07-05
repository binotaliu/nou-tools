<x-layout
    :title="'訂閱行事曆 - ' . ($viewModel->name ?: '我的課表') . ' - NOU 小幫手'"
    :noindex="true"
>
    <div class="mx-auto max-w-2xl">
        <div
            class="mb-8 flex flex-col items-start justify-between gap-3 sm:flex-row"
        >
            <div>
                <h2 class="text-3xl font-bold text-warm-900">訂閱行事曆</h2>
                <p class="mt-2 text-sm text-warm-600">
                    將你的課表訂閱到行事曆應用程式，以自動同步課表更新與接收提醒。
                </p>
            </div>

            <x-link-button
                :href="route('schedules.show', $viewModel->uuid)"
                variant="secondary"
                class="w-full sm:w-auto"
            >
                <x-heroicon-o-arrow-left class="size-4" />
                回到課表
            </x-link-button>
        </div>

        @php
            $icsUrl = $viewModel->calendarUrls->ics;
            $webcalUrl = $viewModel->calendarUrls->webcal;
            $googleUrl = $viewModel->calendarUrls->google;
            $outlookWebUrl = $viewModel->calendarUrls->outlook;
            $calendarSettings = $viewModel->calendarSettings;
            $primaryReminder = old(
                'reminder_offsets.0',
                $calendarSettings['reminder_offsets'][0] ?? 30,
            );
            $secondaryReminder = old(
                'reminder_offsets.1',
                $calendarSettings['reminder_offsets'][1] ?? '',
            );
            $reminderOptions = [
                5 => '課前 5 分鐘',
                10 => '課前 10 分鐘',
                15 => '課前 15 分鐘',
                30 => '課前 30 分鐘',
                60 => '課前 1 小時',
                120 => '課前 2 小時',
                180 => '課前 3 小時',
                1440 => '課前 1 天',
            ];
        @endphp

        <div class="space-y-6">
            {{-- Subscription Methods --}}
            <x-card
                title="選擇訂閱方式"
                subtitle="選擇您常用的行事曆應用程式，點擊按鈕訂閱此課表。"
            >
                <div class="grid gap-3">
                    <x-link-button
                        :href="$webcalUrl"
                        variant="ghost"
                        full-width
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
                        data-analytics-event="calendar_download"
                        data-analytics-feature="schedule"
                        data-analytics-label="ics"
                    >
                        下載 iCal（.ics）
                    </x-link-button>
                </div>
            </x-card>

            {{-- Calendar Settings --}}
            <form
                method="POST"
                action="{{ route('schedules.calendar-settings.update', $viewModel->uuid) }}"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                <x-card
                    title="訂閱設定"
                    subtitle="保存設定後，已訂閱的行事曆會在同步時自動更新。"
                >
                    <div class="space-y-4">
                        <p class="text-sm text-warm-700">
                            修改設定後可能需要數小時才會更新訂閱內容。
                        </p>

                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-warm-200 bg-white px-3 py-2"
                        >
                            <input
                                type="hidden"
                                name="include_school_calendar"
                                value="0"
                            />
                            <input
                                type="checkbox"
                                name="include_school_calendar"
                                value="1"
                                @checked(old('include_school_calendar', $calendarSettings['include_school_calendar']))
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
                                type="hidden"
                                name="include_exams"
                                value="0"
                            />
                            <input
                                type="checkbox"
                                name="include_exams"
                                value="1"
                                @checked(old('include_exams', $calendarSettings['include_exams']))
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
                                    type="hidden"
                                    name="class_reminders_enabled"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    name="class_reminders_enabled"
                                    value="1"
                                    @checked(old('class_reminders_enabled', $calendarSettings['class_reminders_enabled']))
                                    class="size-4 rounded border-warm-400 text-warm-700 focus:ring-warm-500"
                                />
                                <span class="text-sm font-medium text-warm-800">
                                    面授課程提醒
                                </span>
                            </label>

                            <span class="mt-1 block text-sm text-warm-600">
                                註：此設定僅支援 Apple
                                日曆與其他相容的行事曆應用程式，
                                <strong>Google 日曆需要手動設定提醒</strong>
                                。
                            </span>

                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-semibold text-warm-700"
                                    >
                                        第一次提醒
                                    </label>

                                    <x-select
                                        name="reminder_offsets[]"
                                        class="bg-white"
                                    >
                                        @foreach ($reminderOptions as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected((string) $primaryReminder === (string) $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                </div>

                                <div>
                                    <label
                                        class="mb-1 block text-xs font-semibold text-warm-700"
                                    >
                                        第二次提醒（可留空）
                                    </label>

                                    <x-select
                                        name="reminder_offsets[]"
                                        class="bg-white"
                                        wrapper-class="w-full"
                                    >
                                        <option value="">
                                            不設定第二次提醒
                                        </option>
                                        @foreach ($reminderOptions as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected((string) $secondaryReminder === (string) $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>
                        </div>

                        @if ($errors->has('reminder_offsets.*'))
                            <p class="text-sm text-red-700">
                                {{ $errors->first('reminder_offsets.*') }}
                            </p>
                        @endif
                    </div>
                </x-card>

                {{-- Action Buttons --}}
                <div class="flex flex-col gap-2 sm:flex-row-reverse">
                    <x-button
                        type="submit"
                        variant="primary"
                        class="w-full sm:w-auto"
                    >
                        儲存設定
                    </x-button>

                    <x-link-button
                        :href="route('schedules.show', $viewModel->uuid)"
                        variant="warm-subtle"
                        class="w-full sm:w-auto"
                    >
                        回到課表
                    </x-link-button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
