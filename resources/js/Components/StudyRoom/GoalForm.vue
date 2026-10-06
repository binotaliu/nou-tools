<script setup>
import { reactive, ref } from 'vue'
import TimeField from '../TimeField.vue'
import { GOAL_WEEKDAYS } from '../../Composables/useStudyGoal'

const props = defineProps({
  goal: { type: Object, required: true },
  push: { type: Object, required: true },
})

function blank(value) {
  return value === null || value === undefined ? '' : String(value)
}

const form = reactive({
  weeklyGoalMinutes: blank(props.goal.weeklyGoalMinutes),
  days: Object.fromEntries(
    GOAL_WEEKDAYS.map(({ value }) => [
      value,
      {
        minutes: blank(props.goal.dailyGoals[value]?.minutes),
        remindAt: blank(props.goal.dailyGoals[value]?.remindAt),
      },
    ])
  ),
})

const reminderError = ref('')

function toNumberOrNull(value) {
  return value === '' ? null : Number(value)
}

function submit() {
  const dailyGoals = {}

  for (const { value } of GOAL_WEEKDAYS) {
    const day = form.days[value]

    dailyGoals[value] = {
      minutes: toNumberOrNull(day.minutes),
      remindAt: day.remindAt === '' ? null : day.remindAt,
    }
  }

  props.goal.save({
    weeklyGoalMinutes: toNumberOrNull(form.weeklyGoalMinutes),
    dailyGoals,
  })
}

// Turning the reminder on needs the browser's permission and a push
// subscription first; turning it off only clears the flag, because the
// subscription is shared with the other notifications.
async function onReminderChange(event) {
  const wanted = event.target.checked
  reminderError.value = ''

  if (wanted && !(await props.push.enable())) {
    event.target.checked = false
    reminderError.value = '沒有取得通知權限，請在瀏覽器設定中允許本站通知。'

    return
  }

  if (!(await props.goal.saveReminder(wanted))) {
    event.target.checked = !wanted
  }
}

const inputClass =
  'w-full rounded-lg border border-theme-200 bg-white px-2 py-1.5 text-sm text-theme-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100'
</script>

<template>
  <form
    class="space-y-5"
    data-testid="study-room-goal-form"
    @submit.prevent="submit"
  >
    <div>
      <label
        for="study-goal-weekly"
        class="mb-1 block text-sm font-semibold text-theme-900 dark:text-zinc-100"
      >
        每週目標（分鐘）
      </label>
      <input
        id="study-goal-weekly"
        v-model="form.weeklyGoalMinutes"
        type="number"
        min="1"
        max="6000"
        inputmode="numeric"
        placeholder="不設定"
        :class="inputClass"
        data-testid="study-room-goal-weekly"
      />
      <p
        v-if="goal.errors.weeklyGoalMinutes"
        class="mt-1 text-xs text-red-700 dark:text-red-400"
      >
        {{ goal.errors.weeklyGoalMinutes[0] }}
      </p>
    </div>

    <fieldset>
      <legend
        class="mb-1 text-sm font-semibold text-theme-900 dark:text-zinc-100"
      >
        每日目標與提醒時間
      </legend>
      <p class="mb-2 text-xs text-theme-700 dark:text-zinc-400">
        每週與每日目標可以只設定其中一種，也可以同時設定。時間為臺北時間。
      </p>
      <div class="space-y-2">
        <div
          v-for="day in GOAL_WEEKDAYS"
          :key="day.value"
          class="grid grid-cols-[3rem_1fr_1fr] items-center gap-2"
        >
          <span class="text-sm text-theme-900 dark:text-zinc-100">{{
            day.label
          }}</span>
          <input
            v-model="form.days[day.value].minutes"
            type="number"
            min="1"
            max="1440"
            inputmode="numeric"
            placeholder="分鐘"
            :aria-label="`${day.label}目標分鐘`"
            :class="inputClass"
            :data-testid="`study-room-goal-minutes-${day.value}`"
          />
          <TimeField
            v-model="form.days[day.value].remindAt"
            :label="`${day.label}提醒時間`"
            :input-class="inputClass"
            :clear-test-id="`study-room-goal-remind-clear-${day.value}`"
            :data-testid="`study-room-goal-remind-${day.value}`"
          />
        </div>
      </div>
    </fieldset>

    <div>
      <label
        class="flex items-center gap-2 text-sm text-theme-900 dark:text-zinc-100"
      >
        <input
          type="checkbox"
          :checked="goal.notifyOnGoalReminder"
          data-testid="study-room-goal-reminder-checkbox"
          @change="onReminderChange"
        />
        在提醒時間傳送推播通知（目標已達成時不會提醒）
      </label>
      <p
        v-if="reminderError"
        class="mt-1 text-xs text-red-700 dark:text-red-400"
        data-testid="study-room-goal-reminder-error"
      >
        {{ reminderError }}
      </p>
    </div>

    <p
      v-if="goal.message"
      class="text-sm text-red-700 dark:text-red-400"
      data-testid="study-room-goal-error"
    >
      {{ goal.message }}
    </p>

    <button
      type="submit"
      :disabled="goal.saving"
      class="w-full rounded-lg bg-theme-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-theme-800 disabled:opacity-60"
      data-testid="study-room-goal-submit"
    >
      儲存目標
    </button>
  </form>
</template>
