import { ref } from 'vue'

// `initial` mirrors { shareUrl }. `shareInputRef` is an optional ref to the
// <input> used for the copy-to-clipboard `execCommand` fallback.
export default function useCopyLink(initial, shareInputRef = null) {
  const shareUrl = ref(initial.shareUrl)
  const copied = ref(false)

  async function copy() {
    try {
      await navigator.clipboard.writeText(shareUrl.value)
    } catch (e) {
      shareInputRef?.value?.select()
      document.execCommand('copy')
    }

    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }

  return { shareUrl, copied, copy }
}
