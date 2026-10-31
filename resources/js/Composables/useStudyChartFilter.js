import { ref, watch } from 'vue'

// 包含面授課: whether the study-room charts (stats modal, progress page)
// count 面授 sessions. Off by default; remembered per browser, shared by both
// views through one module-level ref. A blocked storage just means the
// default for this page.
const KEY = 'nou:study-room:chart-include-in-person:v1'

function read() {
  try {
    return localStorage.getItem(KEY) === '1'
  } catch {
    return false
  }
}

const includeInPerson = ref(read())

watch(includeInPerson, value => {
  try {
    localStorage.setItem(KEY, value ? '1' : '0')
  } catch {
    // Storage blocked: the choice just doesn't outlive the page.
  }
})

export default function useStudyChartFilter() {
  return { includeInPerson }
}
