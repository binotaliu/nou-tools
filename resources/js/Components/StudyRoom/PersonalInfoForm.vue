<script setup>
// Submits fields over axios via profile.submitProfile() (see
// useStudyRoomProfile.js / StudyRoomProfileController) and closes the modal
// itself on success.
import { ref } from 'vue'
import { CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  profile: { type: Object, required: true },
  clockNow: { type: Number, required: true },
  nicknameMinLength: { type: Number, required: true },
  nicknameMaxLength: { type: Number, required: true },
  nicknameCooldownDays: { type: Number, required: true },
  push: { type: Object, required: true },
})

const nicknameInput = ref(props.profile.nickname || '')
const emojiInput = ref(props.profile.emoji)
const playSoundInput = ref(props.profile.playSoundOnTimerEnd)
const notifyInput = ref(props.profile.notifyOnTimerEnd)
const notifyError = ref('')

// Switching notifications on needs the browser's permission and a push
// subscription, both of which must be asked for from a user gesture, so it
// happens here on change rather than on submit. Switching them off only
// clears the flag: the subscription is shared with the class-starting
// reminders, so unsubscribing would quietly switch those off too.
async function onNotifyChange() {
  notifyError.value = ''

  if (!notifyInput.value) {
    return
  }

  if (!props.push.supported) {
    notifyInput.value = false
    notifyError.value = '這個瀏覽器不支援通知。'

    return
  }

  if (!(await props.push.enable())) {
    notifyInput.value = false
    notifyError.value = '沒有取得通知權限，請在瀏覽器設定中允許本站通知。'
  }
}

async function submit() {
  await props.profile.submitProfile({
    nickname: nicknameInput.value,
    emoji: emojiInput.value,
    playSoundOnTimerEnd: playSoundInput.value,
    notifyOnTimerEnd: notifyInput.value,
  })
}
</script>

<template>
  <p class="mb-4 text-sm text-theme-600 dark:text-zinc-400">
    暱稱與表情符號會顯示給其他在自習室裡的同學看到。暱稱每
    {{ nicknameCooldownDays }} 天只能變更一次，表情符號則隨時可以換。
  </p>

  <form
    class="space-y-4"
    data-testid="study-room-profile-form"
    @submit.prevent="submit"
  >
    <div>
      <label
        for="nickname"
        class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
        >暱稱</label
      >
      <input
        id="nickname"
        v-model="nicknameInput"
        type="text"
        name="nickname"
        :minlength="nicknameMinLength"
        :maxlength="nicknameMaxLength"
        required
        :readonly="!profile.canChangeNickname"
        :class="
          !profile.canChangeNickname
            ? 'bg-theme-50 text-theme-500 dark:bg-zinc-800 dark:text-zinc-400'
            : 'bg-white dark:bg-zinc-900'
        "
        data-testid="study-room-nickname-input"
        class="w-full rounded-lg border border-theme-200 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300 dark:border-zinc-700"
      />
      <p
        v-show="!profile.canChangeNickname"
        class="mt-1 text-xs text-theme-500 dark:text-zinc-400"
        data-testid="study-room-nickname-cooldown-note"
      >
        {{ profile.nicknameCooldownLabel(clockNow) }}
      </p>
      <p
        v-if="profile.profileErrors.nickname"
        class="mt-1 text-xs text-red-600 dark:text-red-400"
      >
        {{ profile.profileErrors.nickname[0] }}
      </p>
    </div>

    <div>
      <span
        class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
        >選一個表情符號代表你</span
      >
      <div class="flex flex-wrap gap-2" data-testid="study-room-emoji-choices">
        <label
          v-for="emojiChoice in profile.emojiChoices"
          :key="emojiChoice"
          class="cursor-pointer rounded-lg border border-theme-200 px-3 py-2 text-xl has-[:checked]:border-theme-600 has-[:checked]:bg-theme-100 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-orange-300 dark:border-zinc-700 dark:has-[:checked]:border-theme-400 dark:has-[:checked]:bg-theme-900/40"
        >
          <input
            v-model="emojiInput"
            type="radio"
            name="emoji"
            :value="emojiChoice"
            class="sr-only"
            data-testid="study-room-emoji-option"
            required
          />
          {{ emojiChoice }}
        </label>
      </div>
      <p
        v-if="profile.profileErrors.emoji"
        class="mt-1 text-xs text-red-600 dark:text-red-400"
      >
        {{ profile.profileErrors.emoji[0] }}
      </p>
    </div>

    <div>
      <label
        class="flex cursor-pointer items-center gap-2 text-sm text-theme-700 dark:text-zinc-300"
      >
        <input
          v-model="playSoundInput"
          type="checkbox"
          name="playSoundOnTimerEnd"
          class="size-4 rounded border-theme-300 text-theme-600 focus:ring-orange-300 dark:border-zinc-600"
          data-testid="study-room-play-sound-checkbox"
        />
        時間到時播放音效
      </label>
    </div>

    <div>
      <label
        class="flex cursor-pointer items-center gap-2 text-sm text-theme-700 dark:text-zinc-300"
      >
        <input
          v-model="notifyInput"
          type="checkbox"
          name="notifyOnTimerEnd"
          :disabled="push.busy"
          class="size-4 rounded border-theme-300 text-theme-600 focus:ring-orange-300 disabled:opacity-50 dark:border-zinc-600"
          data-testid="study-room-notify-checkbox"
          @change="onNotifyChange"
        />
        時間到時傳送通知
      </label>
      <p
        v-if="notifyError"
        class="mt-1 text-xs text-red-600 dark:text-red-400"
        data-testid="study-room-notify-error"
      >
        {{ notifyError }}
      </p>
    </div>

    <button
      type="submit"
      :disabled="profile.profileSubmitting"
      data-testid="study-room-profile-submit"
      class="inline-flex items-center gap-1.5 rounded-lg bg-theme-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-theme-900 disabled:opacity-50 dark:bg-theme-600 dark:hover:bg-theme-500"
    >
      <CheckIcon class="size-4" />
      儲存
    </button>
  </form>
</template>
