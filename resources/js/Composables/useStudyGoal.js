import { computed, reactive, ref } from 'vue'

// ISO weekdays (1 = Monday), in the order the form lists them.
export const GOAL_WEEKDAYS = [
  { value: 1, label: '週一' },
  { value: 2, label: '週二' },
  { value: 3, label: '週三' },
  { value: 4, label: '週四' },
  { value: 5, label: '週五' },
  { value: 6, label: '週六' },
  { value: 7, label: '週日' },
]

const TAIPEI_WEEKDAYS = {
  Mon: 1,
  Tue: 2,
  Wed: 3,
  Thu: 4,
  Fri: 5,
  Sat: 6,
  Sun: 7,
}

// Goals and reminder times are Taipei time, whatever the viewer's zone.
function taipeiWeekday(timestamp) {
  const label = new Intl.DateTimeFormat('en-US', {
    weekday: 'short',
    timeZone: 'Asia/Taipei',
  }).format(timestamp)

  return TAIPEI_WEEKDAYS[label]
}

// Goals are saved over axios to StudyRoomGoalController and the returned goal
// view model is applied directly. Reminder opt-in is its own endpoint, since
// it also needs the browser's push permission.
export default function useStudyGoal(initialGoal) {
  const weeklyGoalMinutes = ref(initialGoal.weeklyGoalMinutes)
  const dailyGoals = ref(initialGoal.dailyGoals ?? {})
  const notifyOnGoalReminder = ref(initialGoal.notifyOnGoalReminder)
  const excludeInPersonClass = ref(initialGoal.excludeInPersonClass)

  const open = ref(false)
  const saving = ref(false)
  const errors = ref({})
  const message = ref('')

  function apply(goal) {
    weeklyGoalMinutes.value = goal.weeklyGoalMinutes
    dailyGoals.value = goal.dailyGoals ?? {}
    notifyOnGoalReminder.value = goal.notifyOnGoalReminder
    excludeInPersonClass.value = goal.excludeInPersonClass
  }

  function dailyMinutes(weekday) {
    return dailyGoals.value[weekday]?.minutes ?? null
  }

  function todayMinutes(now) {
    return dailyMinutes(taipeiWeekday(now))
  }

  const hasAnyGoal = computed(
    () =>
      weeklyGoalMinutes.value !== null ||
      Object.values(dailyGoals.value).some(day => day.minutes !== null)
  )

  async function save(form) {
    saving.value = true
    errors.value = {}
    message.value = ''

    try {
      const response = await window.axios.put('/study-room/goal', form)
      apply(response.data.goal)
      open.value = false

      return true
    } catch (error) {
      errors.value = error.response?.data?.errors ?? {}
      message.value = error.response?.data?.message ?? '儲存失敗，請稍後再試。'

      return false
    } finally {
      saving.value = false
    }
  }

  async function saveReminder(enabled) {
    message.value = ''

    try {
      const response = await window.axios.put('/study-room/goal-reminder', {
        enabled,
      })
      apply(response.data.goal)

      return true
    } catch (error) {
      message.value = error.response?.data?.message ?? '儲存失敗，請稍後再試。'

      return false
    }
  }

  return reactive({
    weeklyGoalMinutes,
    dailyGoals,
    notifyOnGoalReminder,
    excludeInPersonClass,
    open,
    saving,
    errors,
    message,
    hasAnyGoal,
    dailyMinutes,
    todayMinutes,
    save,
    saveReminder,
  })
}
