@props([
    'items' => [],
    'hasAnyOverride' => false,
    'scheduleUuid' => null,
])

@php
    use Illuminate\Support\Carbon;

    // Build a plain payload for the client. Each class occurrence carries its
    // absolute instant (Taipei wall-clock -> ISO 8601 with offset) so the
    // frontend can pick the "next" class and sort against the viewer's real
    // clock, correctly for any timezone and even from an offline cache.
    $itemsPayload = collect($items->toCollection())
        ->filter(fn ($item) => $item->courseClass !== null)
        ->map(function ($item) {
            $courseClass = $item->courseClass;

            $schedules = collect($courseClass->schedules->toCollection())
                ->map(function ($schedule) use ($courseClass) {
                    $effectiveStart = $schedule->startTime ?: $courseClass->startTime;
                    $effectiveEnd = $schedule->endTime ?: $courseClass->endTime;
                    $ymd = $schedule->date->format('Y-m-d');

                    $instantStart = Carbon::parse($ymd . ' ' . ($effectiveStart ?: '00:00'), 'Asia/Taipei')->toIso8601String();
                    $instantEnd = Carbon::parse($ymd . ' ' . ($effectiveEnd ?: $effectiveStart ?: '00:00'), 'Asia/Taipei')->toIso8601String();

                    return [
                        'ymd' => $ymd,
                        'startTime' => $effectiveStart,
                        'endTime' => $effectiveEnd,
                        'hasOverride' => $schedule->startTime !== null,
                        'instantStart' => $instantStart,
                        'instantEnd' => $instantEnd,
                    ];
                })
                ->values()
                ->all();

            return [
                'courseName' => $courseClass->courseName,
                'code' => $courseClass->isTentative ? '尚未分班' : $courseClass->code,
                'isTentative' => $courseClass->isTentative,
                'teacherName' => $courseClass->teacherName,
                'courseInfoUrl' => route('course.show', $courseClass->courseId),
                'videoLink' => $courseClass->link,
                'backupClassroomUrl' => $courseClass->backupClassroomUrl,
                'schedules' => $schedules,
            ];
        })
        ->values()
        ->all();

    $editUrl = route('schedules.edit', $scheduleUuid);
@endphp

{{--
    Rendered on the client so "下次上課" and the row order track the viewer's
    real, local time (and survive offline caching). Defined synchronously here
    so the factory exists before the deferred Alpine bundle evaluates x-data.
--}}
<script>
    window.nouScheduleItems =
        window.nouScheduleItems ||
        function (items) {
            const T = window.NouTime

            return {
                items: items,
                now: Date.now(),

                init() {
                    // Keep "next class" and the sort fresh as time passes, and
                    // recompute when a backgrounded/offline-restored tab returns.
                    setInterval(() => {
                        this.now = Date.now()
                    }, 60000)
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) {
                            this.now = Date.now()
                        }
                    })
                },

                // Earliest class that has not yet ended, or null when none remain.
                nextOf(item) {
                    let best = null
                    let bestStart = Infinity

                    for (const schedule of item.schedules) {
                        if (Date.parse(schedule.instantEnd) < this.now) {
                            continue
                        }

                        const start = Date.parse(schedule.instantStart)

                        if (start < bestStart) {
                            bestStart = start
                            best = schedule
                        }
                    }

                    return best
                },

                // Items sorted by their next class; those without an upcoming class
                // fall to the bottom. `i` keeps the sort stable and keys the x-for.
                get rows() {
                    return this.items
                        .map((item, i) => ({
                            item,
                            next: this.nextOf(item),
                            i,
                        }))
                        .sort((a, b) => {
                            const aStart = a.next
                                ? Date.parse(a.next.instantStart)
                                : Infinity
                            const bStart = b.next
                                ? Date.parse(b.next.instantStart)
                                : Infinity

                            return aStart === bStart
                                ? a.i - b.i
                                : aStart - bStart
                        })
                },

                // Split a teacher name so a trailing "老師" can render smaller,
                // mirroring the previous server-side markup.
                teacher(item) {
                    const name = item.teacherName

                    if (!name) {
                        return null
                    }

                    return name.endsWith('老師')
                        ? { base: name.slice(0, -2), suffix: '老師' }
                        : { base: name, suffix: '' }
                },

                // Official Taipei date/time of the next class.
                taipeiDate(next) {
                    return (
                        T.monthDay(next.ymd) +
                        ' (' +
                        T.weekdayFromYmd(next.ymd) +
                        ')'
                    )
                },

                taipeiTime(next) {
                    return next.startTime
                        ? next.startTime + ' ~ ' + next.endTime
                        : null
                },

                // Secondary "your time" line — only when the viewer's zone differs
                // from Taipei. Includes the local date when it lands on a different
                // day than the Taipei date.
                localHint(next) {
                    if (!next.startTime) {
                        return null
                    }

                    const start = new Date(next.instantStart)
                    const end = new Date(next.instantEnd)

                    if (!T.differsFromTaipei(start)) {
                        return null
                    }

                    let datePrefix = ''
                    const localStartYmd = T.localYmd(start)

                    if (localStartYmd !== next.ymd) {
                        datePrefix =
                            T.monthDay(localStartYmd) +
                            ' (' +
                            T.weekdayFromYmd(localStartYmd) +
                            ') '
                    }

                    return (
                        '你的時間 · ' +
                        datePrefix +
                        T.localHM(start) +
                        ' ~ ' +
                        T.localHM(end) +
                        ' (' +
                        T.gmtLabel(start) +
                        ')'
                    )
                },
            }
        }
</script>

<div
    x-data="nouScheduleItems({{ Js::from($itemsPayload) }})"
    class="mb-4 overflow-hidden rounded-lg border border-warm-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
>
    {{-- 桌面版表格 --}}
    <div class="hidden overflow-x-auto md:block print:block">
        <table
            class="w-full border-collapse text-left text-warm-700 dark:text-zinc-300"
            aria-describedby="schedule-items-caption"
        >
            <caption id="schedule-items-caption" class="sr-only">
                課程時間表項目清單
            </caption>

            <thead
                class="border-b-2 border-warm-300 bg-warm-100 dark:border-zinc-600 dark:bg-zinc-900"
            >
                <tr>
                    <th
                        scope="col"
                        class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                    >
                        課程名稱
                    </th>
                    <th
                        scope="col"
                        class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                    >
                        班級
                    </th>
                    <th
                        scope="col"
                        class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100 print:hidden"
                    >
                        下次上課
                    </th>
                    <th
                        scope="col"
                        class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100 print:hidden"
                    >
                        時間
                    </th>
                    <th
                        scope="col"
                        class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100"
                    >
                        教師
                    </th>
                    <th
                        scope="col"
                        class="px-4 py-3 font-bold text-warm-900 dark:text-zinc-100 print:hidden"
                    >
                        <span class="sr-only">動作</span>
                    </th>
                </tr>
            </thead>

            <tbody>
                <template x-for="row in rows" :key="row.i">
                    <tr
                        class="border-b border-warm-200 hover:bg-warm-50 dark:border-zinc-700 dark:hover:bg-zinc-950"
                    >
                        <th
                            scope="row"
                            class="px-4 py-3 font-semibold text-warm-900 dark:text-zinc-100"
                            x-text="row.item.courseName"
                        ></th>

                        <td
                            class="px-4 py-3 text-sm text-warm-800 tabular-nums dark:text-zinc-200"
                        >
                            <span
                                class="inline-block rounded bg-warm-100 px-2 py-1 font-mono text-xs font-normal text-warm-800 dark:bg-zinc-800 dark:text-zinc-200 print:bg-transparent print:p-0"
                                x-show="!row.item.isTentative"
                            >
                                <span class="sr-only">班級代碼：</span>
                                <span x-text="row.item.code"></span>
                            </span>
                            <span
                                x-show="row.item.isTentative"
                                class="ml-1 inline-block rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200 print:bg-transparent print:p-0"
                            >
                                尚未分班
                            </span>
                        </td>

                        <td
                            class="px-4 py-3 text-warm-800 tabular-nums dark:text-zinc-200 print:hidden"
                        >
                            <template x-if="row.next">
                                <span x-text="taipeiDate(row.next)"></span>
                            </template>
                            <template x-if="!row.next">
                                <span
                                    class="text-sm text-warm-500 dark:text-zinc-400"
                                >
                                    無未來課程
                                </span>
                            </template>
                        </td>

                        <td
                            class="px-4 py-3 text-warm-800 tabular-nums dark:text-zinc-200 print:hidden"
                        >
                            <template x-if="row.next && taipeiTime(row.next)">
                                <div>
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <span
                                            x-text="taipeiTime(row.next)"
                                        ></span>
                                        <template x-if="row.next.hasOverride">
                                            <span class="inline-flex">
                                                <x-heroicon-o-exclamation-triangle
                                                    class="size-4 text-warm-500 dark:text-zinc-400"
                                                    title="該次課程時間與一般時間不同"
                                                    aria-hidden="true"
                                                />
                                                <span class="sr-only">
                                                    該次課程時間與一般時間不同。
                                                </span>
                                            </span>
                                        </template>
                                    </span>
                                    <template x-if="localHint(row.next)">
                                        <div
                                            class="text-xs text-warm-500 dark:text-zinc-400"
                                            x-text="localHint(row.next)"
                                        ></div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="row.next && !taipeiTime(row.next)">
                                <span
                                    class="text-sm text-warm-400 dark:text-zinc-500"
                                >
                                    未設定
                                </span>
                            </template>
                        </td>

                        <td class="px-4 py-3 text-warm-800 dark:text-zinc-200">
                            <template x-if="teacher(row.item)">
                                <span
                                    class="inline-flex flex-wrap items-baseline gap-1"
                                    :aria-label="row.item.teacherName"
                                >
                                    <span
                                        class="shrink-0"
                                        x-show="teacher(row.item).base"
                                        x-text="teacher(row.item).base"
                                    ></span>
                                    <span
                                        class="align-text-top text-xs"
                                        x-show="teacher(row.item).suffix"
                                        x-text="teacher(row.item).suffix"
                                    ></span>
                                </span>
                            </template>
                            <template x-if="!teacher(row.item)">
                                <span>−</span>
                            </template>
                        </td>

                        <td
                            class="px-4 py-3 text-warm-800 dark:text-zinc-200 print:hidden"
                        >
                            <a
                                :href="row.item.courseInfoUrl"
                                class="mr-3 inline-flex items-center gap-1 font-semibold text-warm-800 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-200 dark:hover:text-zinc-100"
                                :aria-label="row.item.courseName +
                                ' 的課程資訊'"
                            >
                                <x-heroicon-o-information-circle
                                    class="inline size-4"
                                    aria-hidden="true"
                                />
                                課程資訊
                            </a>

                            <a
                                x-show="row.item.videoLink"
                                :href="row.item.videoLink"
                                target="_blank"
                                rel="noopener"
                                data-offline-allow
                                class="inline-flex items-center gap-1 font-semibold text-warm-500 underline underline-offset-4 hover:text-warm-400 hover:no-underline dark:text-zinc-400 dark:hover:text-zinc-500"
                                :aria-label="'前往 ' +
                                row.item.courseName +
                                ' 的視訊上課連結'"
                            >
                                <x-heroicon-o-video-camera
                                    class="inline size-4"
                                    aria-hidden="true"
                                />
                                視訊上課
                            </a>

                            <a
                                x-show="row.item.backupClassroomUrl"
                                :href="row.item.backupClassroomUrl"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1 font-semibold text-warm-700 underline underline-offset-4 hover:text-warm-900 hover:no-underline dark:text-zinc-300 dark:hover:text-zinc-100"
                                :aria-label="'前往 ' +
                                row.item.courseName +
                                ' 的備用教室連結'"
                            >
                                <x-heroicon-o-squares-plus
                                    class="inline size-4"
                                    aria-hidden="true"
                                />
                                備用教室
                            </a>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- 手機版卡片列表 --}}
    <div class="md:hidden print:hidden">
        <template x-for="row in rows" :key="row.i">
            <div
                class="border-b border-warm-200 last:border-b-0 dark:border-zinc-700"
            >
                <div
                    class="m-0 border-0 border-b border-warm-200 bg-white p-4 transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
                >
                    {{-- 課程名稱 --}}
                    <h3
                        class="mb-2 text-lg font-semibold text-warm-900 dark:text-zinc-100"
                        x-text="row.item.courseName"
                    ></h3>

                    {{-- 班級代碼 --}}
                    <div class="mb-3 flex items-center gap-2">
                        <span
                            class="inline-block rounded bg-warm-100 px-2 py-1 font-mono text-xs font-normal text-warm-800 dark:bg-zinc-800 dark:text-zinc-200"
                            x-show="!row.item.isTentative"
                        >
                            <span class="sr-only">班級代碼：</span>
                            <span x-text="row.item.code"></span>
                        </span>
                        <span
                            x-show="row.item.isTentative"
                            class="inline-block rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            尚未分班
                        </span>

                        <template x-if="teacher(row.item)">
                            <p
                                class="inline-flex items-baseline gap-1 text-warm-900 dark:text-zinc-100"
                            >
                                <span
                                    class="text-sm"
                                    x-show="teacher(row.item).base"
                                    x-text="teacher(row.item).base"
                                ></span>
                                <span
                                    class="text-xs text-warm-700 dark:text-zinc-300"
                                    x-show="teacher(row.item).suffix"
                                    x-text="teacher(row.item).suffix"
                                ></span>
                            </p>
                        </template>
                    </div>

                    {{-- 下次上課 --}}
                    <div class="mb-4 space-y-3">
                        <div>
                            <p
                                class="mb-1 text-xs font-semibold tracking-wide text-warm-600 uppercase dark:text-zinc-400"
                            >下次上課</p>

                            <template x-if="row.next">
                                <div>
                                    <p
                                        class="inline-flex items-center gap-1 font-semibold text-warm-900 dark:text-zinc-100"
                                    >
                                        <span
                                            x-text="taipeiDate(row.next)"
                                        ></span>
                                        <template x-if="taipeiTime(row.next)">
                                            <span
                                                class="inline-flex items-center gap-1"
                                            >
                                                <span
                                                    x-text="
                                                        taipeiTime(row.next)
                                                    "
                                                ></span>
                                                <template
                                                    x-if="row.next.hasOverride"
                                                >
                                                    <x-heroicon-o-exclamation-triangle
                                                        class="size-4 text-warm-500 dark:text-zinc-400"
                                                        title="該次課程時間與一般時間不同"
                                                    />
                                                </template>
                                            </span>
                                        </template>
                                    </p>
                                    <template x-if="localHint(row.next)">
                                        <p
                                            class="text-xs text-warm-500 dark:text-zinc-400"
                                            x-text="localHint(row.next)"
                                        ></p>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!row.next">
                                <p
                                    class="font-semibold text-warm-500 dark:text-zinc-400"
                                >無未來課程</p>
                            </template>
                        </div>
                    </div>

                    {{-- 操作按鈕 --}}
                    <div
                        class="flex gap-2 border-t border-warm-100 pt-3 dark:border-zinc-800"
                    >
                        <a
                            :href="row.item.courseInfoUrl"
                            class="flex-1 rounded px-2 py-2 text-center text-sm font-semibold text-warm-800 underline underline-offset-4 transition hover:bg-warm-50 hover:text-warm-900 dark:text-zinc-200 dark:hover:bg-zinc-950 dark:hover:text-zinc-100"
                        >
                            <x-heroicon-o-information-circle
                                class="mr-1 inline size-4"
                            />
                            課程資訊
                        </a>

                        <a
                            x-show="row.item.videoLink"
                            :href="row.item.videoLink"
                            target="_blank"
                            rel="noopener"
                            data-offline-allow
                            class="flex-1 rounded px-2 py-2 text-center text-sm font-semibold text-warm-500 underline underline-offset-4 transition hover:bg-orange-50 hover:text-warm-400 dark:text-zinc-400 dark:hover:text-zinc-500"
                        >
                            <x-heroicon-o-video-camera
                                class="mr-1 inline size-4"
                            />
                            視訊上課
                        </a>

                        <a
                            x-show="row.item.backupClassroomUrl"
                            :href="row.item.backupClassroomUrl"
                            target="_blank"
                            rel="noopener"
                            class="flex-1 rounded px-2 py-2 text-center text-sm font-semibold text-warm-600 underline underline-offset-4 transition hover:bg-warm-50 hover:text-warm-500 dark:text-zinc-400 dark:hover:bg-zinc-950 dark:hover:text-zinc-400"
                        >
                            <x-heroicon-o-squares-plus
                                class="mr-1 inline size-4"
                            />
                            備用教室
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- 溫馨提示 (時間異動) --}}
    @if ($hasAnyOverride)
        <div
            class="flex items-center gap-1 border-t border-warm-200 bg-warm-50 px-4 py-2 text-xs text-warm-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400"
        >
            <x-heroicon-o-exclamation-triangle
                class="size-4 text-warm-500 dark:text-zinc-400"
            />
            <span>表示該次課程時間與一般時間不同</span>
        </div>
    @endif
</div>
