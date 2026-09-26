<script setup>
// Submits fields over axios via profile.submitProfile() (see
// useStudyRoomProfile.js / StudyRoomProfileController) and closes the modal
// itself on success.
import { computed, nextTick, ref } from 'vue'
import { CheckIcon } from '@heroicons/vue/24/outline'
import FieldError from '../FieldError.vue'
import { focusFirstInvalid } from '../../Composables/useFormErrorFocus'
import { emojiName } from '../../study-room-emoji-names'

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
const formElement = ref(null)

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

const nicknameDescribedBy = computed(
  () =>
    [
      props.profile.canChangeNickname ? null : 'nickname-cooldown-note',
      props.profile.profileErrors.nickname ? 'nickname-error' : null,
    ]
      .filter(Boolean)
      .join(' ') || null
)

async function submit() {
  const saved = await props.profile.submitProfile({
    nickname: nicknameInput.value,
    emoji: emojiInput.value,
    playSoundOnTimerEnd: playSoundInput.value,
    notifyOnTimerEnd: notifyInput.value,
  })

  if (!saved) {
    await nextTick()
    focusFirstInvalid(formElement.value)
  }
}
</script>

<template>
  <p class="mb-4 text-sm text-theme-700 dark:text-zinc-400">
    暱稱與表情符號會顯示給其他在自習室裡的同學看到。暱稱每
    {{ nicknameCooldownDays }} 天只能變更一次，表情符號則隨時可以換。
  </p>

  <form
    ref="formElement"
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
        autocomplete="nickname"
        enterkeyhint="done"
        aria-required="true"
        :aria-invalid="profile.profileErrors.nickname ? 'true' : null"
        :aria-describedby="nicknameDescribedBy"
        :minlength="nicknameMinLength"
        :maxlength="nicknameMaxLength"
        required
        :readonly="!profile.canChangeNickname"
        :class="
          !profile.canChangeNickname
            ? 'bg-theme-50 text-theme-700 dark:bg-zinc-800 dark:text-zinc-400'
            : 'bg-white dark:bg-zinc-900'
        "
        data-testid="study-room-nickname-input"
        class="w-full rounded-lg border border-zinc-500 px-3 py-2 text-sm focus:border-theme-300 focus:ring-theme-300 dark:border-zinc-500"
      />
      <p
        v-show="!profile.canChangeNickname"
        id="nickname-cooldown-note"
        class="mt-1 text-xs text-theme-700 dark:text-zinc-400"
        data-testid="study-room-nickname-cooldown-note"
      >
        {{ profile.nicknameCooldownLabel(clockNow) }}
      </p>
      <FieldError
        id="nickname-error"
        :message="profile.profileErrors.nickname?.[0]"
      />
    </div>

    <fieldset>
      <legend
        id="emoji-legend"
        class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
      >
        選一個表情符號代表你
      </legend>
      <div class="flex flex-wrap gap-2" data-testid="study-room-emoji-choices">
        <label
          v-for="emojiChoice in profile.emojiChoices"
          :key="emojiChoice"
          class="cursor-pointer rounded-lg border border-theme-200 px-3 py-2 text-xl has-[:checked]:border-theme-600 has-[:checked]:bg-theme-100 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-theme-300 dark:border-zinc-700 dark:has-[:checked]:border-theme-400 dark:has-[:checked]:bg-theme-900/40"
        >
          <input
            v-model="emojiInput"
            type="radio"
            name="emoji"
            :value="emojiChoice"
            :aria-label="emojiName(emojiChoice)"
            class="sr-only"
            data-testid="study-room-emoji-option"
            :aria-invalid="profile.profileErrors.emoji ? 'true' : null"
            :aria-describedby="
              profile.profileErrors.emoji ? 'emoji-error' : null
            "
            required
          />
          <span aria-hidden="true">{{ emojiChoice }}</span>
        </label>
      </div>
      <FieldError
        id="emoji-error"
        :message="profile.profileErrors.emoji?.[0]"
      />
    </fieldset>

    <div>
      <label
        class="flex cursor-pointer items-center gap-2 text-sm text-theme-700 dark:text-zinc-300"
      >
        <input
          v-model="playSoundInput"
          type="checkbox"
          name="playSoundOnTimerEnd"
          class="size-4 rounded border-zinc-500 text-theme-700 focus:ring-theme-300 dark:border-zinc-500"
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
          class="size-4 rounded border-zinc-500 text-theme-700 focus:ring-theme-300 disabled:opacity-50 dark:border-zinc-500"
          data-testid="study-room-notify-checkbox"
          :aria-describedby="notifyError ? 'notify-error' : null"
          @change="onNotifyChange"
        />
        時間到時傳送通知
      </label>
      <p
        v-if="notifyError"
        id="notify-error"
        role="alert"
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
      class="inline-flex items-center gap-1.5 rounded-lg bg-theme-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-theme-900 disabled:opacity-50 dark:bg-theme-700 dark:hover:bg-theme-800"
    >
      <CheckIcon class="size-4" />
      儲存
    </button>
  </form>
</template>
