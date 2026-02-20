<x-layout :title="($schedule->name ?: '我的課表') . ' - NOU 小幫手'">
    <div class="mx-auto max-w-5xl">
        <div
            class="mb-8 flex flex-col items-start justify-between gap-y-4 md:flex-row"
        >
            <div>
                <h2 class="mb-2 text-3xl font-bold text-warm-900">
                    {{ $schedule->name ?: '我的課表' }}
                </h2>
                <p
                    class="mt-1 flex items-center gap-1 text-sm text-warm-600 print:hidden"
                >
                    <x-heroicon-o-information-circle class="inline size-4" />
                    小提示：將此頁加入瀏覽器書籤，下次即可快速開啟課表。
                </p>
            </div>

            <div
                class="flex w-full gap-2 md:w-auto print:hidden"
                x-data="{ subscribeOpen: false }"
            >
                <x-link-button
                    :href="route('schedules.edit', $schedule)"
                    variant="secondary"
                >
                    <x-heroicon-o-pencil-square class="size-4" />
                    編輯課表
                </x-link-button>

                @php
                    $icsUrl = route('schedules.calendar', $schedule);
                    $webcalUrl = preg_replace('/^https?/', 'webcal', $icsUrl);
                    $googleUrl = 'https://calendar.google.com/calendar/r?cid=' . urlencode($webcalUrl);
                    $outlookWebUrl = 'https://outlook.office.com/calendar/0/addfromweb?url=' . urlencode($webcalUrl);
                @endphp

                <x-button
                    type="button"
                    variant="primary"
                    @click="subscribeOpen = true"
                >
                    <x-heroicon-o-calendar class="inline size-4" />
                    訂閱行事曆
                </x-button>

                {{-- Subscribe modal --}}
                <x-modal
                    name="subscribeOpen"
                    title="訂閱行事曆"
                    description="選擇要使用的方式來訂閱或下載您的課表行事曆："
                >
                    <div class="grid gap-3">
                        <x-link-button
                            :href="$webcalUrl"
                            variant="ghost"
                            full-width
                            @click="subscribeOpen = false"
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
                        >
                            Microsoft 365 / Outlook.com
                        </x-link-button>

                        <x-link-button
                            :href="$icsUrl"
                            variant="ghost"
                            full-width
                            target="_blank"
                            rel="noopener"
                            :download="true"
                            @click="subscribeOpen = false"
                        >
                            下載 iCal（.ics）
                        </x-link-button>
                    </div>

                    <x-slot:footer>
                        <x-button
                            type="button"
                            variant="warm-subtle"
                            size="sm"
                            @click="subscribeOpen = false"
                            class="px-4 py-2 text-sm"
                        >
                            取消
                        </x-button>
                    </x-slot>
                </x-modal>
            </div>
        </div>

        <x-greeting class="mb-4 print:hidden" />

        {{-- Schedule Items - Responsive Table/Cards --}}
        <x-schedule-items :items="$schedule->items" :schedule="$schedule" />

        <x-common-links class="mb-8 print:hidden" />

        {{-- Schedule Calendar View --}}
        @if (count($schedule->items) > 0)
            <div class="mb-8">
                <h3 class="mb-4 text-2xl font-bold text-warm-900">面授日期</h3>
                @php
                    $hasAnyOverride = $schedule->items->contains(function ($item) {
                        return $item->courseClass->schedules->contains(function ($s) {
                            return $s->start_time !== null;
                        });
                    });
                @endphp

                @if ($hasAnyOverride)
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
                    @php
                        $coursesByMonth = [];
                        foreach ($schedule->items as $item) {
                            foreach ($item->courseClass->schedules as $classSchedule) {
                                $monthKey = $classSchedule->date->format('Y-m');
                                $monthKey_display = $classSchedule->date->format('Y 年 n 月');
                                if (! isset($coursesByMonth[$monthKey])) {
                                    $coursesByMonth[$monthKey] = ['month' => $monthKey_display, 'dates' => []];
                                }
                                $dateKey = $classSchedule->date->format('Y-m-d');
                                if (! isset($coursesByMonth[$monthKey]['dates'][$dateKey])) {
                                    $coursesByMonth[$monthKey]['dates'][$dateKey] = [];
                                }
                                // Use schedule time override if it exists, otherwise use class default
                                $displayStartTime = $classSchedule->start_time ?? $item->courseClass->start_time;
                                $displayEndTime = $classSchedule->end_time ?? $item->courseClass->end_time;
                                $hasOverride = $classSchedule->start_time !== null;

                                $coursesByMonth[$monthKey]['dates'][$dateKey][] = [
                                    'courseName' => $item->courseClass->course->name,
                                    'code' => $item->courseClass->code,
                                    'time' => $displayStartTime ? $displayStartTime . ' - ' . $displayEndTime : '未設定',
                                    'hasOverride' => $hasOverride,
                                    'date' => $classSchedule->date,
                                ];
                            }
                        }
                    @endphp

                    @foreach (collect($coursesByMonth)->sortKeys() as $monthData)
                        <x-card :title="$monthData['month']">
                            <div
                                class="grid grid-cols-1 space-y-3 gap-x-6 gap-y-1 print:grid-cols-2"
                            >
                                @foreach (collect($monthData['dates'])->sortKeys() as $dateStr => $courses)
                                    <div
                                        class="break-inside-avoid-page border-l-4 border-orange-500 py-2 pl-4"
                                    >
                                        @php
                                            $d = \Carbon\Carbon::parse($dateStr);
                                            $weekdayZh = ['日', '一', '二', '三', '四', '五', '六'][$d->dayOfWeek];
                                        @endphp

                                        <div
                                            class="mb-1 font-semibold text-warm-900"
                                        >
                                            {{ $d->format('n/j') }}
                                            ({{ $weekdayZh }})
                                        </div>
                                        <div class="space-y-1">
                                            @foreach ($courses as $course)
                                                <div
                                                    class="text-sm text-warm-700"
                                                >
                                                    <span class="font-semibold">
                                                        {{ $course['courseName'] }}
                                                    </span>
                                                    <x-class-code>
                                                        {{ $course['code'] }}
                                                    </x-class-code>
                                                    <br />
                                                    <span
                                                        class="inline-flex items-center gap-1 text-warm-600"
                                                    >
                                                        {{ $course['time'] }}
                                                        @if ($course['hasOverride'])
                                                            <x-heroicon-o-exclamation-triangle
                                                                class="size-4 text-orange-600"
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
        <x-school-calendar class="mb-8" />

        @php
            // Collect unique courses that appear in this schedule and sort by earliest exam datetime (ascending)
            $courses = $schedule->items
                ->map(fn ($it) => $it->courseClass->course)
                ->unique('id')
                ->values();

            $coursesWithExam = $courses
                ->filter(fn ($c) => $c->midterm_date || $c->final_date || $c->exam_time_start || $c->exam_time_end)
                ->map(function ($course) {
                    $dates = collect();

                    if ($course->midterm_date) {
                        $dt = \Illuminate\Support\Carbon::parse($course->midterm_date);
                        if ($course->exam_time_start) {
                            $dt = $dt->setTimeFromTimeString($course->exam_time_start);
                        }
                        $dates->push($dt);
                    }

                    if ($course->final_date) {
                        $dt = \Illuminate\Support\Carbon::parse($course->final_date);
                        if ($course->exam_time_start) {
                            $dt = $dt->setTimeFromTimeString($course->exam_time_start);
                        }
                        $dates->push($dt);
                    }

                    // attach earliest exam datetime (or null)
                    $course->earliest_exam_at = $dates->count() ? $dates->min() : null;

                    return $course;
                })
                ->sortBy(function ($c) {
                    return $c->earliest_exam_at ? $c->earliest_exam_at->getTimestamp() : PHP_INT_MAX;
                })
                ->values();
        @endphp

        @if ($coursesWithExam->isNotEmpty())
            <x-card
                class="mb-8"
                title="考試資訊"
                subtitle="以下為您加入課表的科目之期中 / 期末考試日期與節次。"
            >
                {{-- 手機：卡片列表 --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($coursesWithExam as $course)
                        @php
                            $firstClass = $schedule->items->first(fn ($it) => $it->courseClass->course->id === $course->id)?->courseClass;
                        @endphp

                        <div
                            class="rounded-lg border border-warm-200 bg-white p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="font-semibold text-warm-900">
                                        {{ $course->name }}
                                    </div>
                                    @if ($firstClass)
                                        <div class="mt-1">
                                            <x-class-code>
                                                {{ $firstClass->code }}
                                            </x-class-code>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <p
                                        class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase"
                                    >
                                        期中考
                                    </p>
                                    @if ($course->midterm_date)
                                        @php
                                            $md = \Illuminate\Support\Carbon::parse($course->midterm_date);
                                            $weekday = ['日', '一', '二', '三', '四', '五', '六'][$md->dayOfWeek];
                                        @endphp

                                        <div
                                            class="font-semibold text-warm-900"
                                        >
                                            {{ $md->format('n/j') }}
                                            ({{ $weekday }})
                                        </div>

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div
                                                class="mt-1 text-sm text-warm-600"
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
                                    @else
                                        <div class="text-warm-500">—</div>
                                    @endif
                                </div>

                                <div>
                                    <p
                                        class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase"
                                    >
                                        期末考
                                    </p>
                                    @if ($course->final_date)
                                        @php
                                            $fd = \Illuminate\Support\Carbon::parse($course->final_date);
                                            $weekday = ['日', '一', '二', '三', '四', '五', '六'][$fd->dayOfWeek];
                                        @endphp

                                        <div
                                            class="font-semibold text-warm-900"
                                        >
                                            {{ $fd->format('n/j') }}
                                            ({{ $weekday }})
                                        </div>

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div
                                                class="mt-1 text-sm text-warm-600"
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
                                    @else
                                        <div class="text-warm-500">—</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- 桌面：維持表格，但只在 md+ 顯示 --}}
                <div class="hidden overflow-x-auto md:block">
                    <table
                        class="w-full border-collapse overflow-hidden rounded"
                    >
                        <thead>
                            <tr
                                class="rounded-t border-b-2 border-warm-300 bg-warm-100"
                            >
                                <th
                                    class="px-4 py-3 text-left font-bold text-warm-900"
                                >
                                    課程
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-bold text-warm-900"
                                >
                                    期中考
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-bold text-warm-900"
                                >
                                    期末考
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($coursesWithExam as $course)
                                @php
                                    $firstClass = $schedule->items->first(fn ($it) => $it->courseClass->course->id === $course->id)?->courseClass;
                                @endphp

                                <tr
                                    class="border-b border-warm-200 hover:bg-warm-50"
                                >
                                    <td
                                        class="px-4 py-3 font-semibold text-warm-900"
                                    >
                                        {{ $course->name }}
                                        @if ($firstClass)
                                            <div class="mt-1">
                                                <x-class-code>
                                                    {{ $firstClass->code }}
                                                </x-class-code>
                                            </div>
                                        @endif
                                    </td>

                                    <td
                                        class="px-4 py-3 text-warm-800 tabular-nums"
                                    >
                                        @if ($course->midterm_date)
                                            @php
                                                $md = \Illuminate\Support\Carbon::parse($course->midterm_date);
                                                $weekday = ['日', '一', '二', '三', '四', '五', '六'][$md->dayOfWeek];
                                            @endphp

                                            <div class="font-semibold">
                                                {{ $md->format('n/j') }}
                                                ({{ $weekday }})
                                            </div>
                                        @else
                                            <div class="text-warm-500">—</div>
                                        @endif

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div
                                                class="mt-1 text-sm text-warm-600"
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
                                    </td>

                                    <td
                                        class="px-4 py-3 text-warm-800 tabular-nums"
                                    >
                                        @if ($course->final_date)
                                            @php
                                                $fd = \Illuminate\Support\Carbon::parse($course->final_date);
                                                $weekday = ['日', '一', '二', '三', '四', '五', '六'][$fd->dayOfWeek];
                                            @endphp

                                            <div class="font-semibold">
                                                {{ $fd->format('n/j') }}
                                                ({{ $weekday }})
                                            </div>
                                        @else
                                            <div class="text-warm-500">—</div>
                                        @endif

                                        @if ($course->exam_time_start || $course->exam_time_end)
                                            <div
                                                class="mt-1 text-sm text-warm-600"
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
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif

        {{-- Share Section --}}
        <x-card>
            <div class="flex items-center justify-between gap-4 print:flex">
                <div class="md:flex-1 print:flex-1">
                    <p class="mb-3 text-warm-700">
                        您可以使用以下連結來編輯或檢視此課表，請妥善保管此連結。
                        <br />
                        <span
                            class="inline-flex items-center gap-1 font-semibold text-red-600"
                        >
                            <x-heroicon-o-exclamation-triangle class="size-4" />
                            注意：任何擁有此連結的人都可以編輯您的課表。
                        </span>
                    </p>

                    <div
                        x-data="{
                            shareUrl: {{ Js::from(url(route('schedules.show', $schedule))) }},
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
                    <div class="rounded border border-warm-200 bg-white p-2">
                        {!! DNS2D::getBarcodeSVG(url(route('schedules.show', $schedule)), 'QRCODE') !!}
                    </div>
                </div>
            </div>
        </x-card>

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
    </div>
</x-layout>
