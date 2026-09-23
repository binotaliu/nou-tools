import { ref } from 'vue'

const STORAGE_KEY = 'nou:learning-progress:view-mode:v1'
const MODES = ['table', 'week', 'subject']

function readStorage() {
  try {
    return window.localStorage.getItem(STORAGE_KEY)
  } catch {
    return null
  }
}

function writeStorage(value) {
  try {
    window.localStorage.setItem(STORAGE_KEY, value)
  } catch {
    // Private windows / blocked storage: forget the preference silently.
  }
}

export default function useLearningProgressViewMode(defaultMode = 'week') {
  const stored = readStorage()
  const viewMode = ref(MODES.includes(stored) ? stored : defaultMode)

  function setViewMode(mode) {
    if (!MODES.includes(mode)) {
      return
    }

    viewMode.value = mode
    writeStorage(mode)
  }

  return { viewMode, setViewMode }
}
