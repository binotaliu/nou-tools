import { ref, watch } from 'vue'

// Reactions are tied to the browser session (there is no login), so the
// server keeps one per session per issue: tapping another emoji switches it,
// tapping the chosen one withdraws it. State is updated optimistically and
// replaced by the server's tallies once the PUT returns.
//
// `source` is a getter for `{ reactions: { options, mine }, reactionUrl }`;
// it is watched because Inertia reuses the page component when paging from
// one issue to another.
export default function useNewsletterReactions(source) {
  const options = ref([])
  const mine = ref(null)
  const pending = ref(false)

  function sync() {
    const page = source()

    options.value = page.reactions.options.map(option => ({ ...option }))
    mine.value = page.reactions.mine
  }

  sync()
  watch(() => source().reactionUrl, sync)

  function tally(key, delta) {
    const option = options.value.find(candidate => candidate.key === key)

    if (option) {
      option.count = Math.max(0, option.count + delta)
    }
  }

  async function react(key) {
    if (pending.value) {
      return
    }

    const previous = {
      options: options.value.map(o => ({ ...o })),
      mine: mine.value,
    }
    const next = mine.value === key ? null : key

    if (mine.value !== null) {
      tally(mine.value, -1)
    }

    if (next !== null) {
      tally(next, 1)
    }

    mine.value = next
    pending.value = true

    try {
      const response = await window.axios.put(source().reactionUrl, {
        reaction: next,
      })

      options.value = response.data.reactions.options
      mine.value = response.data.reactions.mine
    } catch (error) {
      options.value = previous.options
      mine.value = previous.mine
    } finally {
      pending.value = false
    }
  }

  return { options, mine, pending, react }
}
