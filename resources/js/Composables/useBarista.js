import { computed, onUnmounted, reactive, ref } from 'vue'

const CHARACTERS_PER_SECOND = 32

function prefersReducedMotion() {
  return (
    document.documentElement.hasAttribute('data-reduce-motion') ||
    window.matchMedia('(prefers-reduced-motion: reduce)').matches
  )
}

// One conversation with the barista: a line from GET /cafe/barista, typed out
// like a game dialogue box (instantly under reduced motion), then the reply
// buttons. Choosing a reply shows the barista's answer as the next "line"
// and reports its action to the page (`order`, `note`) through `onAction`.
// The words always sit in a live region in full, so a screen reader never
// waits for the typewriter.
export default function useBarista({ onAction }) {
  const line = ref(null)
  const answer = ref(null)
  const typed = ref('')
  const loading = ref(false)
  const failed = ref(false)

  let typer = null

  const fullText = computed(() => answer.value?.text ?? line.value?.text ?? '')
  const mood = computed(() => line.value?.mood ?? 'happy')
  const replies = computed(() =>
    answer.value ? [] : (line.value?.replies ?? [])
  )
  const typing = computed(() => typed.value.length < fullText.value.length)

  function type(text) {
    clearInterval(typer)

    if (prefersReducedMotion()) {
      typed.value = text
      return
    }

    typed.value = ''
    let shown = 0

    typer = setInterval(() => {
      shown += Math.max(1, Math.round(CHARACTERS_PER_SECOND / 20))
      typed.value = text.slice(0, shown)

      if (shown >= text.length) {
        clearInterval(typer)
      }
    }, 50)
  }

  function skipTyping() {
    clearInterval(typer)
    typed.value = fullText.value
  }

  async function talk(drink = null) {
    loading.value = true
    failed.value = false
    answer.value = null

    try {
      const response = await window.axios.get('/cafe/barista', {
        params: drink ? { drink } : {},
      })

      line.value = response.data.line
      failed.value = line.value === null
      type(fullText.value)
    } catch {
      line.value = null
      failed.value = true
    } finally {
      loading.value = false
    }
  }

  function choose(reply) {
    answer.value = { text: reply.response }
    type(reply.response)

    if (reply.action && reply.action !== 'none') {
      onAction(reply.action)
    }
  }

  onUnmounted(() => clearInterval(typer))

  return reactive({
    line,
    typed,
    fullText,
    mood,
    replies,
    loading,
    failed,
    typing,
    talk,
    choose,
    skipTyping,
    answered: computed(() => answer.value !== null),
  })
}
