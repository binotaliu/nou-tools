<div
    x-data="nouGreeting({
                semesterLabel: {{ Js::from($semesterLabel) }},
                semesterCode: {{ Js::from($semesterCode) }},
                semesterStart: {{ Js::from($semesterStart) }},
                semesterEnd: {{ Js::from($semesterEnd) }},
            })"
    x-on:click="toggleCompact()"
    data-testid="greeting-widget"
    class="cursor-pointer select-none"
>
    <x-card
        {{ $attributes->class(['print:hidden'])->merge() }}
        x-bind:class="{ 'px-4 py-2': compactMode }"
    >
        <template x-if="!compactMode">
            <div
                data-testid="greeting-normal"
                class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div class="flex flex-col justify-between gap-1">
                    <heading
                        class="text-xl font-semibold sm:text-2xl md:text-3xl"
                    >
                        <span x-text="greetingText">早安</span>
                        ，歡迎回來！
                    </heading>

                    <p class="text-warm-500 dark:text-zinc-400">
                        今天是
                        <span x-text="dateString"></span>
                        ，
                        <span x-text="semesterInfo"></span>
                    </p>
                </div>

                <template x-if="showTaiwanClock">
                    <div
                        data-testid="taiwan-clock"
                        class="flex shrink-0 flex-row items-center justify-between gap-3 border-t border-warm-200 pt-3 sm:flex-col sm:items-end sm:justify-start sm:border-t-0 sm:border-l sm:pt-0 sm:pl-4 dark:border-zinc-700"
                    >
                        <div
                            class="inline-flex items-center text-2xl font-semibold text-warm-700 tabular-nums sm:text-3xl dark:text-zinc-300"
                        >
                            <span x-text="taiwanHour"></span>
                            <span class="blink-colon">:</span>
                            <span x-text="taiwanMinute"></span>
                        </div>
                        <div class="text-center">
                            <p
                                class="text-xs text-warm-500 tabular-nums dark:text-zinc-400"
                                x-text="taiwanDateString"
                            ></p>
                            <p
                                class="text-[0.65rem] text-warm-400 dark:text-zinc-500"
                            >台灣時間</p>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="compactMode">
            <div
                data-testid="greeting-compact"
                class="flex flex-row items-center justify-between gap-3 text-sm text-warm-500 tabular-nums dark:text-zinc-400"
            >
                <span>
                    <span x-text="compactDateString"></span>
                    ・
                    <span x-text="compactSemesterInfo"></span>
                </span>

                <span
                    x-show="showTaiwanClock"
                    data-testid="taiwan-clock-compact"
                >
                    台灣時間:
                    <span x-text="taiwanHour"></span>
                    :
                    <span x-text="taiwanMinute"></span>
                </span>
            </div>
        </template>
    </x-card>
</div>
