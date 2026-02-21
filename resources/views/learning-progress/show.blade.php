@php
    use Illuminate\Support\Str;

    $semesterLabel = Str::toSemesterDisplay($viewModel->term);
    $currentWeek = $viewModel->getCurrentWeek();
@endphp

<x-layout
    :title="'學習進度表 - ' . $semesterLabel . ' - ' . $viewModel->scheduleName . '- NOU 小幫手'"
    :noindex="true"
>
    <div class="mx-auto max-w-7xl">
        {{-- Header --}}
        <div
            class="mb-8 flex flex-col items-start justify-between gap-y-4 md:flex-row"
        >
            <div>
                <h2 class="mb-2 text-3xl font-bold text-warm-900">
                    學習進度表
                    <small>— {{ $viewModel->scheduleName }}</small>
                </h2>
                <p class="text-lg text-warm-700">
                    {{ $semesterLabel }}
                </p>
            </div>

            <div class="flex gap-2 print:hidden">
                <x-link-button
                    :href="route('schedules.show', $viewModel->scheduleUuid)"
                    variant="secondary"
                >
                    <x-heroicon-o-arrow-left class="size-4" />
                    回到課表
                </x-link-button>

                <x-button
                    type="button"
                    variant="primary"
                    onclick="document.getElementById('progress-form').submit()"
                >
                    <x-heroicon-o-check class="size-4" />
                    保存進度
                </x-button>
            </div>
        </div>

        <x-greeting class="mb-6" />

        {{-- overall completion progress bar --}}
        <div class="mb-4 w-full print:hidden">
            <p class="mb-1 text-sm text-warm-700">
                本學期完成進度：{{ number_format($viewModel->percentage, 0) }}%
            </p>
            <div
                class="relative h-2 w-full overflow-hidden rounded bg-warm-200"
            >
                <div
                    class="h-full bg-warm-500"
                    style="width: {{ $viewModel->percentage }}%"
                ></div>
            </div>
        </div>

        {{-- Learning Progress Table --}}
        <div
            class="relative rounded border border-warm-300"
            x-data="{
                showHorizontalGradient: false,
                showVerticalGradient: false,
                dirty: false,
                unloadListener: null,

                init() {
                    this.checkGradientVisibility()
                },

                checkGradientVisibility() {
                    const progressForm = this.$refs.progressForm
                    this.showHorizontalGradient =
                        progressForm.scrollHeight > progressForm.clientHeight &&
                        progressForm.scrollTop + progressForm.clientHeight <
                            progressForm.scrollHeight
                    this.showVerticalGradient =
                        progressForm.scrollWidth > progressForm.clientWidth &&
                        progressForm.scrollLeft + progressForm.clientWidth <
                            progressForm.scrollWidth
                },
            }"
            x-cloak
            x-on:resize.window="checkGradientVisibility()"
            x-init="$nextTick(() => checkGradientVisibility())"
        >
            <form
                id="progress-form"
                method="POST"
                action="{{ route('learning-progress.update', [$viewModel->scheduleUuid, $viewModel->term]) }}"
                class="max-h-180 max-w-full overflow-x-auto rounded bg-linear-to-b from-warm-100 to-white print:max-h-full"
                style="
                    --courses-count: {{ count($viewModel->courses) }};
                    --weeks-count: {{ count($viewModel->weeks) }};
                "
                x-ref="progressForm"
                x-on:scroll.decounce="checkGradientVisibility()"
            >
                @csrf
                @method('PUT')

                <table
                    class="w-full min-w-4xl table-fixed border-collapse rounded print:min-w-0"
                >
                    <thead class="print:table-header-group">
                        <tr
                            class="sticky top-0 z-20 rounded-t bg-warm-100 print:static"
                        >
                            <th
                                class="sticky left-0 z-30 w-24 rounded-tl border border-t-0 border-l-0 border-warm-300 bg-warm-100 px-0 py-2 text-center text-sm font-bold text-warm-900 print:static"
                                rowspan="2"
                            >
                                週次 \ 課程

                                <div
                                    class="absolute top-full left-0 h-px w-full bg-warm-300 print:hidden"
                                ></div>
                            </th>
                            @foreach ($viewModel->courses as $course)
                                <th
                                    class="relative w-[calc((100%-6rem)/var(--courses-count))] border border-t-0 border-warm-300 px-2 py-2 text-center font-bold text-warm-900 last:rounded-tr last:border-r-0 print:static"
                                    colspan="2"
                                >
                                    <div
                                        class="line-clamp-2 w-full overflow-hidden text-xs"
                                    >
                                        {{ $course['name'] }}
                                    </div>

                                    <div
                                        class="absolute top-full left-0 h-px w-full bg-warm-300 print:hidden"
                                    ></div>
                                </th>
                            @endforeach
                        </tr>
                        <tr
                            class="hidden border-b border-warm-300 bg-warm-100 print:table-row"
                        >
                            @foreach ($viewModel->courses as $course)
                                <th
                                    class="border border-t-0 border-b-0 border-warm-300 px-0 py-1 text-center text-xs font-medium text-warm-700"
                                >
                                    影音
                                </th>
                                <th
                                    class="border border-t-0 border-b-0 border-warm-300 px-0 py-1 text-center text-xs font-medium text-warm-700 last:border-r-0"
                                >
                                    課本
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($viewModel->weeks as $week)
                            <tr
                                class="border-b border-warm-300 hover:bg-warm-50"
                            >
                                <td
                                    @class([
                                        'sticky left-0 z-10 break-inside-avoid border border-b-0 border-l-0 border-warm-300 px-0 py-0 font-semibold text-warm-900 print:static print:bg-warm-50',
                                        match (true) {
                                            $currentWeek === $week['num'] => 'bg-blue-50',
                                            collect($viewModel->courses)->every(
                                                fn ($course) => $viewModel->isProgressComplete(
                                                    $course['id'],
                                                    $week['num'],
                                                ),
                                            )
                                                => 'bg-white [&>div]:text-gray-400',
                                            $viewModel->isWeekPassed($week['num']) &&
                                                collect($viewModel->courses)->contains(
                                                    fn ($course) => ! $viewModel->isProgressComplete(
                                                        $course['id'],
                                                        $week['num'],
                                                    ),
                                                )
                                                => 'bg-red-50',
                                            default => 'bg-warm-50',
                                        },
                                    ])
                                    rowspan="2"
                                >
                                    <div
                                        class="text-center text-xs font-semibold print:text-black!"
                                    >
                                        第{{ Str::toChineseNumber($week['num']) }}週
                                    </div>
                                    <div
                                        class="text-center text-xs text-warm-600 print:text-warm-600!"
                                    >
                                        {{ $week['start'] }} -
                                        {{ $week['end'] }}
                                    </div>

                                    {{-- we need this thing to mimic the border of the first column when the header is sticky --}}
                                    <div
                                        class="absolute top-0 left-full h-full w-px bg-warm-300 print:hidden"
                                    ></div>
                                </td>
                                @foreach ($viewModel->courses as $course)
                                    <td
                                        @class([
                                            'border border-warm-300 text-center last:border-r-0 [&:has(input:checked)]:bg-white',
                                            match (true) {
                                                $currentWeek === $week['num'] => 'bg-blue-50',
                                                $viewModel->isWeekPassed($week['num']) => 'bg-red-50',
                                                default => 'bg-white',
                                            },
                                        ])
                                    >
                                        <label
                                            class="group flex h-full w-full cursor-pointer items-center justify-center gap-1 px-2 py-3"
                                        >
                                            <x-learning-progress-checkbox
                                                :name="'progress[' . $course['id'] . '][' . $week['num'] . '][video]'"
                                                :checked="$viewModel->progress[$course['id']][$week['num']]['video'] ?? false"
                                            />
                                            <span
                                                class="text-xs group-has-checked:text-gray-400 print:hidden"
                                            >
                                                影音
                                            </span>
                                        </label>
                                    </td>
                                    <td
                                        @class([
                                            'border border-warm-300 text-center last:border-r-0 [&:has(input:checked)]:bg-white',
                                            match (true) {
                                                $currentWeek === $week['num'] => 'bg-blue-50',
                                                $viewModel->isWeekPassed($week['num']) => 'bg-red-50',
                                                default => 'bg-white',
                                            },
                                        ])
                                    >
                                        <label
                                            class="group flex h-full w-full cursor-pointer items-center justify-center gap-1 px-2 py-3"
                                        >
                                            <x-learning-progress-checkbox
                                                :name="'progress[' . $course['id'] . '][' . $week['num'] . '][textbook]'"
                                                :checked="$viewModel->progress[$course['id']][$week['num']]['textbook'] ?? false"
                                            />
                                            <span
                                                class="text-xs group-has-checked:text-gray-400 print:hidden"
                                            >
                                                課本
                                            </span>
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($viewModel->courses as $course)
                                    <td
                                        @class(['border border-b-0 border-warm-300 last:border-r-0 print:h-16', 'bg-white'])
                                        colspan="2"
                                    >
                                        {{-- Note textarea --}}
                                        <textarea
                                            name="notes[{{ $course['id'] }}][{{ $week['num'] }}]"
                                            placeholder="（尚未設定目標）"
                                            @class([
                                                'm-0 h-full w-full resize-none px-2 py-2 text-xs placeholder-gray-400 focus:border-blue-500 focus:outline-none print:text-black print:placeholder-transparent',
                                                $viewModel->isProgressComplete($course['id'], $week['num'])
                                                    ? 'text-gray-400'
                                                    : 'text-warm-700',
                                            ])
                                            rows="2"
                                        >
{{ $viewModel->getNote($course['id'], $week['num']) }}</textarea
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </form>

            {{-- Gradient overlay --}}
            {{-- horizontal gradient to hide the scrollbar, only on screen, not in print --}}
            <div
                :class="showHorizontalGradient ? 'opacity-100' : 'opacity-0'"
                class="pointer-events-none absolute bottom-0 left-0 z-50 h-32 w-[calc(100%-2px)] bg-linear-to-t from-stone-900/30 to-transparent transition-opacity duration-150 ease-in print:hidden"
            ></div>

            {{-- vertical gradient to hide the border when the first column is sticky --}}
            <div
                :class="showVerticalGradient ? 'opacity-100' : 'opacity-0'"
                class="pointer-events-none absolute top-0 right-0 z-50 h-[calc(100%-2px)] w-32 bg-linear-to-l from-stone-900/30 to-transparent transition-opacity duration-150 ease-in print:hidden"
            ></div>
        </div>

        {{-- Print button --}}
        <div class="mt-6 flex items-start justify-between">
            {{-- Legend --}}
            <div class="bg-warm-50 print:hidden">
                <p class="mb-2 text-sm font-semibold text-warm-900">圖例：</p>
                <div class="flex items-center justify-start gap-4">
                    <div class="flex items-center gap-2">
                        <div
                            class="size-3 rounded border-2 border-blue-500 bg-blue-50"
                        ></div>
                        <span class="text-xs text-warm-700">目前週次</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            class="size-3 rounded border-2 border-red-400 bg-red-50"
                        ></div>
                        <span class="text-xs text-red-700">
                            進度落後（未完成）
                        </span>
                    </div>
                </div>
            </div>
            <x-button
                type="button"
                variant="warm-subtle"
                onclick="window.print()"
                class="print:hidden"
            >
                <x-heroicon-o-printer class="inline size-4" />
                列印
            </x-button>
        </div>
    </div>
</x-layout>
