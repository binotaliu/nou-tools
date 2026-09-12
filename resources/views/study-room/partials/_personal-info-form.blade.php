<p class="mb-4 text-sm text-warm-600 dark:text-zinc-400">暱稱與表情符號會顯示給其他在自習室裡的同學看到。暱稱每 {{ config('study-room.nickname.cooldown_days') }} 天只能變更一次，表情符號則隨時可以換。</p>

<form
    method="POST"
    action="{{ route('study-room.profile.update') }}"
    class="space-y-4"
    data-testid="study-room-profile-form"
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
            :readonly="!canChangeNickname"
            :class="!canChangeNickname
                ? 'bg-warm-50 text-warm-500 dark:bg-zinc-800 dark:text-zinc-400'
                : 'bg-white dark:bg-zinc-900'"
            data-testid="study-room-nickname-input"
            class="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
        />
        <p
            x-show="!canChangeNickname"
            x-cloak
            x-text="nicknameCooldownLabel()"
            class="mt-1 text-xs text-warm-500 dark:text-zinc-400"
            data-testid="study-room-nickname-cooldown-note"
        ></p>
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
