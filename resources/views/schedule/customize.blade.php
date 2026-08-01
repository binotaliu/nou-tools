<x-layout
    :title="'自訂課表 - ' . ($viewModel->schedule->name ?: '我的課表') . ' - NOU 小幫手'"
    :noindex="true"
>
    <div class="mx-auto max-w-4xl">
        <div
            class="mb-8 flex flex-col items-start justify-between gap-3 sm:flex-row"
        >
            <div>
                <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
                    自訂課表顯示
                </h2>
                <p class="mt-2 text-sm text-warm-600 dark:text-zinc-400">調整課表頁顯示區塊，並在「常用連結」加入你的自訂連結。</p>
            </div>

            <x-link-button
                :href="route('schedules.show', $viewModel->schedule)"
                variant="secondary"
                class="w-full sm:w-auto"
            >
                <x-heroicon-o-arrow-left class="size-4" />
                回到課表
            </x-link-button>
        </div>

        <form
            method="POST"
            action="{{ route('schedules.customize.update', $viewModel->schedule) }}"
            class="space-y-6"
            x-data="nouScheduleCustomize({
                links: {{ Js::encode(old('custom_links', $viewModel->customLinks->toArray())) }},
            })"
        >
            @csrf
            @method('PUT')

            <x-card
                title="顯示區塊"
                subtitle="取消勾選即可在課表頁隱藏對應區塊。"
            >
                @php
                    $displayOptionLabels = [
                        'show_greeting' => ['showGreeting', '問候語區塊'],
                        'show_schedule_items' => ['showScheduleItems', '課程清單'],
                        'show_common_links' => ['showCommonLinks', '常用連結'],
                        'show_class_dates' => ['showClassDates', '面授日期'],
                        'show_school_calendar' => ['showSchoolCalendar', '學校行事曆'],
                        'show_exam_info' => ['showExamInfo', '考試資訊'],
                        'show_announcements' => ['showAnnouncements', '最新公告'],
                        'show_share_section' => ['showShareSection', '分享連結與 QRCode'],
                        'show_print_button' => ['showPrintButton', '列印按鈕'],
                    ];
                @endphp

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($displayOptionLabels as $key => [$property, $label])
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-warm-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <input
                                type="hidden"
                                name="display_options[{{ $key }}]"
                                value="0"
                            />
                            <input
                                type="checkbox"
                                name="display_options[{{ $key }}]"
                                value="1"
                                @checked((bool) old('display_options.' . $key, $viewModel->displayOptions->{$property}))
                                class="size-4 rounded border-warm-400 text-warm-700 focus:ring-warm-500 dark:border-zinc-600 dark:text-zinc-300"
                            />
                            <span
                                class="text-sm font-medium text-warm-800 dark:text-zinc-200"
                            >
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <p class="mt-4 text-sm text-warm-600 dark:text-zinc-400">想調整「最新公告」要顯示哪些分類？前往
                <a href="{{ route('schedules.announcement-preferences', $viewModel->schedule) }}" class="font-medium text-orange-700 hover:underline dark:text-orange-400"> 公告分類設定 </a>
                。</p>
            </x-card>

            <x-card
                title="常用連結：自訂連結"
                subtitle="最多可新增 20 筆。請輸入完整網址（以 https:// 開頭）。僅限 *.nou.edu.tw、line.me、docs.google.com 網域。"
            >
                @if ($errors->has('custom_links') || $errors->has('custom_links.*.title') || $errors->has('custom_links.*.url'))
                    <div
                        class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-3">
                    <template x-for="(link, index) in links" :key="index">
                        <div
                            class="rounded-lg border border-warm-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <div
                                class="grid gap-3 md:grid-cols-[1fr_2fr_auto] md:items-end"
                            >
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-semibold text-warm-800 dark:text-zinc-200"
                                    >
                                        連結名稱
                                    </label>
                                    <input
                                        type="text"
                                        :name="'custom_links[' +
                                        index +
                                        '][title]'"
                                        x-model="link.title"
                                        maxlength="50"
                                        placeholder="例如：我的課程群組"
                                        class="w-full rounded-lg border border-warm-300 px-3 py-2 text-sm focus:border-warm-500 focus:outline-none dark:border-zinc-600"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="mb-1 block text-sm font-semibold text-warm-800 dark:text-zinc-200"
                                    >
                                        網址
                                    </label>
                                    <input
                                        type="url"
                                        :name="'custom_links[' +
                                        index +
                                        '][url]'"
                                        x-model="link.url"
                                        maxlength="2048"
                                        placeholder="https://example.com"
                                        class="w-full rounded-lg border border-warm-300 px-3 py-2 text-sm focus:border-warm-500 focus:outline-none dark:border-zinc-600"
                                    />
                                </div>

                                <x-button
                                    type="button"
                                    variant="danger"
                                    size="sm"
                                    @click="removeLink(index)"
                                >
                                    移除
                                </x-button>
                            </div>
                        </div>
                    </template>

                    <template x-if="links.length === 0">
                        <div
                            class="rounded-lg border border-dashed border-warm-300 bg-warm-50 px-4 py-6 text-center text-sm text-warm-600 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400"
                        >
                            尚未新增自訂連結。
                        </div>
                    </template>

                    <x-button
                        type="button"
                        variant="ghost"
                        @click="addLink()"
                        ::disabled="links.length >= 20"
                    >
                        <x-heroicon-o-plus class="size-4" />
                        新增連結
                    </x-button>
                </div>
            </x-card>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-button
                    type="submit"
                    variant="primary"
                    class="w-full sm:w-auto"
                >
                    <x-heroicon-o-check class="size-4" />
                    儲存自訂設定
                </x-button>

                <x-link-button
                    :href="route('schedules.show', $viewModel->schedule)"
                    variant="secondary"
                    class="w-full sm:w-auto"
                >
                    取消
                </x-link-button>
            </div>
        </form>
    </div>
</x-layout>
