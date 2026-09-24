import { nextTick, reactive, ref } from 'vue'

// The study room's screen-reader voice. LiveAnnouncer.vue renders the two
// regions, which must stay mounted for the page's lifetime: a live region
// that appears together with its text is not reliably announced.
//
// say() clears the region first and writes the text on the next tick, so a
// message identical to the previous one (two 已暫停 in a row) is still
// spoken instead of being treated as unchanged content.
export default function useStudyRoomAnnouncer() {
  const politeMessage = ref('')
  const assertiveMessage = ref('')

  async function say(text, { assertive = false } = {}) {
    if (!text) {
      return
    }

    const target = assertive ? assertiveMessage : politeMessage

    target.value = ''
    await nextTick()
    target.value = text
  }

  return reactive({
    politeMessage,
    assertiveMessage,
    say,
  })
}
