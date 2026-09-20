import { reactive, ref } from 'vue'

// Profile updates are submitted over axios to
// app/Http/Controllers/StudyRoomProfileController.php (JSON-only), and the
// returned profile view model is applied to local state directly, with no
// navigation.
export default function useStudyRoomProfile(initialProfile, emojiChoices) {
  const nickname = ref(initialProfile.nickname)
  const emoji = ref(initialProfile.emoji)
  const canChangeNickname = ref(initialProfile.canChangeNickname)
  const canChangeNicknameAt = ref(initialProfile.canChangeNicknameAt)
  const playSoundOnTimerEnd = ref(initialProfile.playSoundOnTimerEnd)
  const notifyOnTimerEnd = ref(initialProfile.notifyOnTimerEnd)

  const personalInfoOpen = ref(false)
  const profileErrors = ref({})
  const profileSubmitting = ref(false)

  const statsOpen = ref(false)
  const statsLoading = ref(false)
  const statsFetched = ref(false)
  const statsDays = ref([])
  const selectedStatsDate = ref(null)

  function openPersonalInfo() {
    personalInfoOpen.value = true
  }

  function closePersonalInfo() {
    personalInfoOpen.value = false
  }

  function applyProfile(viewModel) {
    nickname.value = viewModel.nickname
    emoji.value = viewModel.emoji
    canChangeNickname.value = viewModel.canChangeNickname
    canChangeNicknameAt.value = viewModel.canChangeNicknameAt
    playSoundOnTimerEnd.value = viewModel.playSoundOnTimerEnd
    notifyOnTimerEnd.value = viewModel.notifyOnTimerEnd
  }

  async function submitProfile(form) {
    profileSubmitting.value = true
    profileErrors.value = {}

    try {
      const response = await window.axios.post('/study-room/profile', form)
      applyProfile(response.data.profile)
      closePersonalInfo()

      return true
    } catch (error) {
      profileErrors.value =
        (error.response && error.response.data && error.response.data.errors) ||
        {}

      return false
    } finally {
      profileSubmitting.value = false
    }
  }

  function nicknameCooldownLabel(clockNow) {
    if (canChangeNickname.value || !canChangeNicknameAt.value) {
      return ''
    }

    const days = Math.ceil(
      (Date.parse(canChangeNicknameAt.value) - clockNow) / 86400000
    )

    return days > 0 ? '可於 ' + days + ' 天後修改' : '可於今天內修改'
  }

  function openStats() {
    statsOpen.value = true
    loadStats()
  }

  async function loadStats() {
    if (statsFetched.value || statsLoading.value) {
      return
    }

    statsLoading.value = true

    try {
      const response = await window.axios.get('/study-room/sessions/stats')
      statsDays.value = response.data.days
      statsFetched.value = true
      selectedStatsDate.value = statsDays.value.length
        ? statsDays.value[statsDays.value.length - 1].date
        : null
    } catch (error) {
      // Passive — stats are a nice-to-have inside the modal.
    } finally {
      statsLoading.value = false
    }
  }

  function selectStatsDate(date) {
    selectedStatsDate.value = date
  }

  function selectedStatsDay() {
    return (
      statsDays.value.find(day => day.date === selectedStatsDate.value) || {
        date: null,
        label: '',
        focusSeconds: 0,
        sessions: [],
      }
    )
  }

  function statsBarHeightStyle(day) {
    const max = Math.max(
      ...statsDays.value.map(candidate => candidate.focusSeconds),
      1
    )
    const percent = Math.max(Math.round((day.focusSeconds / max) * 100), 4)

    return { height: percent + '%' }
  }

  function formatDurationLabel(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)

    return hours > 0 ? hours + ' 小時 ' + minutes + ' 分' : minutes + ' 分'
  }

  function sessionDurationLabel(session) {
    const label = formatDurationLabel(session.focusSeconds)

    if (!session.overtimeSeconds) {
      return label
    }

    return (
      label + '（超時 ' + formatDurationLabel(session.overtimeSeconds) + '）'
    )
  }

  return reactive({
    nickname,
    emoji,
    canChangeNickname,
    canChangeNicknameAt,
    playSoundOnTimerEnd,
    notifyOnTimerEnd,
    personalInfoOpen,
    profileErrors,
    profileSubmitting,
    statsOpen,
    statsLoading,
    statsDays,
    selectedStatsDate,
    emojiChoices,
    openPersonalInfo,
    closePersonalInfo,
    submitProfile,
    nicknameCooldownLabel,
    openStats,
    selectStatsDate,
    selectedStatsDay,
    statsBarHeightStyle,
    formatDurationLabel,
    sessionDurationLabel,
  })
}
