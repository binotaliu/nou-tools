import { reactive, ref, watch } from 'vue'

// 語音提示: what the study room says on its own besides its milestones.
// Per browser (localStorage), which suits a screen-reader preference: it
// belongs to the device with the reader, not the profile. Both default to
// off, and a blocked storage just means the defaults for this page.
const INTERVAL_KEY = 'nou:study-room:announce-interval:v1'
const ROOM_KEY = 'nou:study-room:announce-room:v1'

export const INTERVAL_OPTIONS = [
  { value: 0, label: '不報時' },
  { value: 5, label: '每 5 分鐘' },
  { value: 10, label: '每 10 分鐘' },
  { value: 15, label: '每 15 分鐘' },
]

export const ROOM_OPTIONS = [
  { value: 'off', label: '關閉' },
  { value: 'neighbors', label: '鄰座與同桌' },
  { value: 'floor', label: '同一樓層' },
]

function read(key, allowed, fallback) {
  try {
    const stored = localStorage.getItem(key)
    const match = allowed.find(option => String(option.value) === stored)

    return match ? match.value : fallback
  } catch {
    return fallback
  }
}

function persist(key, value) {
  try {
    localStorage.setItem(key, String(value))
  } catch {
    // Storage blocked: the choice just doesn't outlive the page.
  }
}

export default function useStudyRoomVoiceSettings() {
  const intervalMinutes = ref(read(INTERVAL_KEY, INTERVAL_OPTIONS, 0))
  const roomActivity = ref(read(ROOM_KEY, ROOM_OPTIONS, 'off'))

  watch(intervalMinutes, value => persist(INTERVAL_KEY, value))
  watch(roomActivity, value => persist(ROOM_KEY, value))

  return reactive({ intervalMinutes, roomActivity })
}
