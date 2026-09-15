import { ref } from 'vue'

// Vue port of the `nouArticleShare` Alpine.data() component
// (resources/js/alpine-components.js). `initial` mirrors
// { shareTitle, shareUrl }. `shareInputRef` is an optional ref to the
// fallback <input> used for the copy-to-clipboard `execCommand` fallback
// (the Alpine version used `$refs.shareInput`).
export default function useArticleShare(initial, shareInputRef = null) {
  const showShareModal = ref(false)
  const copied = ref(false)
  const shareTitle = ref(initial.shareTitle)
  const shareUrl = ref(initial.shareUrl)

  async function share() {
    if (navigator.share) {
      try {
        await navigator.share({
          title: shareTitle.value,
          url: shareUrl.value,
        })
      } catch (e) {
        // User cancelled the share sheet; nothing to do.
      }

      return
    }

    showShareModal.value = true
  }

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

  return { showShareModal, copied, shareTitle, shareUrl, share, copy }
}
