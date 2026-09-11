@push('head')
    @vite(['resources/js/echo.js'])
@endpush

<x-layout
    title="自習室 - NOU 小幫手"
    description="陪空大同學一起讀書的虛擬自習室：找個座位坐下、掛上暱稱與表情符號、跑一輪番茄鐘。"
>
    <div class="mx-auto max-w-6xl space-y-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="space-y-2">
                <h2 class="text-3xl font-bold text-warm-900 dark:text-zinc-100">
                    自習室
                </h2>
                <p class="text-sm text-warm-600 dark:text-zinc-400">找個座位坐下，掛上暱稱與表情符號，讓遠距離讀書不再孤單。開放時間：{{ $viewModel->openHoursLabel }}。</p>
            </div>

            <div
                class="inline-flex items-center gap-2 self-start rounded-full bg-warm-100 px-4 py-2 text-sm font-medium text-warm-800 dark:bg-zinc-800 dark:text-zinc-200"
                data-testid="study-room-occupant-count"
            >
                <x-heroicon-o-user-group class="size-4 shrink-0" />
                目前在線 {{ $viewModel->roomState->totals->occupantCount }} 人
            </div>
        </div>

        {{-- 公告板 --}}
        <x-card title="公告板" data-testid="study-room-announcement">
            <div
                class="prose prose-sm max-w-none prose-warm dark:prose-zinc dark:prose-invert"
            >
                {!! $viewModel->announcementHtml !!}
            </div>
        </x-card>

        @if (! $viewModel->hasSchedule)
            <x-card data-testid="study-room-needs-schedule">
                <div class="flex flex-col items-center gap-3 py-6 text-center">
                    <x-heroicon-o-table-cells
                        class="size-10 text-warm-400 dark:text-zinc-500"
                    />
                    <div class="space-y-1">
                        <h3
                            class="text-xl font-semibold text-warm-800 dark:text-zinc-200"
                        >
                            先建立課表才能進自習室
                        </h3>
                        <p class="text-sm text-warm-500 dark:text-zinc-400">自習室會用你的課表列出「你在讀什麼」的選項，所以需要先有一份儲存好的課表。</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-2">
                        <x-link-button
                            :href="route('schedules.create')"
                            variant="warm-dark"
                        >
                            <x-heroicon-o-plus class="size-4" />
                            建立我的課表
                        </x-link-button>
                        <x-link-button
                            :href="route('schedules.my')"
                            variant="secondary"
                        >
                            我已經有課表了
                        </x-link-button>
                    </div>
                </div>
            </x-card>
        @else
            @if ($viewModel->needsProfile)
                <x-card
                    title="設定暱稱與表情符號"
                    subtitle="暱稱與表情符號會顯示給其他在自習室裡的同學看到。暱稱每 {{ config('study-room.nickname.cooldown_days') }} 天只能變更一次，表情符號則隨時可以換。"
                    data-testid="study-room-profile-form"
                >
                    <form
                        method="POST"
                        action="{{ route('study-room.profile.update') }}"
                        class="space-y-4"
                    >
                        @csrf

                        <div>
                            <label
                                for="nickname"
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                暱稱
                            </label>
                            <input
                                type="text"
                                id="nickname"
                                name="nickname"
                                value="{{ old('nickname', $viewModel->profile->nickname) }}"
                                minlength="{{ config('study-room.nickname.min_length') }}"
                                maxlength="{{ config('study-room.nickname.max_length') }}"
                                required
                                data-testid="study-room-nickname-input"
                                class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
                            />
                        </div>

                        <div>
                            <span
                                class="mb-1 block text-sm font-medium text-warm-700 dark:text-zinc-300"
                            >
                                選一個表情符號代表你
                            </span>
                            <div
                                class="flex flex-wrap gap-2"
                                data-testid="study-room-emoji-choices"
                            >
                                @foreach ($viewModel->emojiChoices as $emojiChoice)
                                    <label
                                        class="cursor-pointer rounded-lg border border-warm-200 px-3 py-2 text-xl has-[:checked]:border-warm-600 has-[:checked]:bg-warm-100 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-orange-300 dark:border-zinc-700 dark:has-[:checked]:border-warm-400 dark:has-[:checked]:bg-warm-900/40"
                                    >
                                        <input
                                            type="radio"
                                            name="emoji"
                                            value="{{ $emojiChoice }}"
                                            class="sr-only"
                                            data-testid="study-room-emoji-option"
                                            {{ old('emoji', $viewModel->profile->emoji) === $emojiChoice ? 'checked' : '' }}
                                            required
                                        />
                                        {{ $emojiChoice }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <x-button
                            type="submit"
                            variant="warm-dark"
                            data-testid="study-room-profile-submit"
                        >
                            <x-heroicon-o-check class="size-4" />
                            儲存
                        </x-button>
                    </form>
                </x-card>
            @endif

            <noscript>
                <x-card data-testid="study-room-noscript">
                    <p class="text-sm text-warm-600 dark:text-zinc-400">自習室的即時座位圖與計時器需要 JavaScript 才能運作，請啟用 JavaScript 後重新整理這個頁面。</p>
                </x-card>
            </noscript>

            <div
                x-data="nouStudyRoom({
                            roomState: {{ Js::encode($viewModel->roomState) }},
                            clientConfig: {{ Js::encode($viewModel->clientConfig) }},
                            subjects: {{ Js::encode($viewModel->subjects) }},
                            verbs: {{ Js::encode($viewModel->verbs) }},
                            hasSchedule: {{ Js::from($viewModel->hasSchedule) }},
                            needsProfile: {{ Js::from($viewModel->needsProfile) }},
                        })"
                x-cloak
                data-testid="study-room-root"
            >
                {{-- 即時座位圖將於後續版本加入 --}}
                <div data-testid="study-room-seat-grid"></div>
            </div>
        @endif
    </div>
</x-layout>
